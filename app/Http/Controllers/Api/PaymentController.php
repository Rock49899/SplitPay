<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Enrollment;
use App\Models\Installment;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Student;
use App\Services\PayPlusService;

class PaymentController extends Controller
{
    use FiltersByAnnexe;

    protected $payplusService;

    public function __construct(PayPlusService $payplusService)
    {
        $this->payplusService = $payplusService;
    }

    /**
     * Recalcule les montants dérivés après changement de statut d'un paiement.
     * - installment.amount_paid (échéance)
     * - payment_link.status
     * - enrollment.amount_paid (global scolarité annuelle)
     */
    private function syncDerivedAmounts(Payment $payment): void
    {
        $this->syncInstallmentAmountPaid($payment);
        $this->syncPaymentLinkStatus($payment);
        $this->syncEnrollmentAmountPaidForTuitionYear($payment);
    }

    private function resolveInstallmentForPayment(Payment $payment): ?Installment
    {
        if ($payment->installment_id) {
            return $payment->installment;
        }

        // Paiements historiques sans échéance : rattacher à la première échéance du lien
        $installment = $payment->paymentLink?->installments()->orderBy('tranche_number')->first();

        if ($installment) {
            $payment->update(['installment_id' => $installment->id]);
        }

        return $installment;
    }

    private function syncInstallmentAmountPaid(Payment $payment): void
    {
        $installment = $this->resolveInstallmentForPayment($payment);
        if (!$installment) {
            return;
        }

        $isFirstInstallment = !Installment::query()
            ->where('payment_link_id', $installment->payment_link_id)
            ->where('tranche_number', '<', $installment->tranche_number ?? 1)
            ->exists();

        $paid = (float) Payment::query()
            ->where('status', 'success')
            ->where(function ($q) use ($installment, $isFirstInstallment) {
                $q->where('installment_id', $installment->id);

                // Paiements historiques non rattachés : comptés sur la première échéance
                if ($isFirstInstallment) {
                    $q->orWhere(fn ($q2) => $q2->whereNull('installment_id')
                        ->where('payment_link_id', $installment->payment_link_id));
                }
            })
            ->sum('amount');

        $installment->update([
            'amount_paid' => $paid,
            'status' => $paid >= (float) $installment->amount ? 'used' : 'active',
        ]);
    }

    private function syncPaymentLinkStatus(Payment $payment): void
    {
        $link = $payment->paymentLink;
        if (!$link) {
            return;
        }

        if ($link->installments()->exists()) {
            $fullyPaid = $link->isFullyPaid();
        } else {
            $paid = (float) Payment::query()
                ->where('payment_link_id', $link->id)
                ->where('status', 'success')
                ->sum('amount');
            $fullyPaid = $paid >= (float) $link->amount;
        }

        if ($fullyPaid) {
            $link->update(['status' => 'used']);
        } elseif ($link->status === 'used') {
            // Un paiement annulé rouvre le lien (un lien expiré reste expiré)
            $link->update(['status' => 'active']);
        }
    }

    /**
     * Met à jour enrollment.amount_paid à partir de TOUS les paiements success
     * de TOUS les payment links type tuition du même étudiant et de la même année.
     */
    private function syncEnrollmentAmountPaidForTuitionYear(Payment $payment): void
    {
        $studentId = $payment->student_id;
        $link = $payment->paymentLink;

        if (!$studentId || !$link || $link->type !== 'tuition' || empty($link->school_year)) {
            return;
        }

        $schoolYear = $link->school_year;

        $totalTuitionPaidForYear = (float) Payment::query()
            ->where('student_id', $studentId)
            ->where('status', 'success')
            ->whereHas('paymentLink', function ($q) use ($schoolYear) {
                $q->where('type', 'tuition')
                  ->where('school_year', $schoolYear);
            })
            ->sum('amount');

        $enrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('school_year', $schoolYear)
            ->first();

        if (!$enrollment) {
            return;
        }

        $updates = [
            'amount_paid' => $totalTuitionPaidForYear,
        ];

        if ($enrollment->status !== 'abandoned') {
            $updates['status'] = $totalTuitionPaidForYear >= (float) $enrollment->tuition_amount
                ? 'completed'
                : 'active';
        }

        $enrollment->update($updates);
    }

    /**
     * Applique le statut CONFIRMÉ par l'API PayPlus (jamais celui envoyé par un tiers).
     * Idempotent : un paiement déjà réussi n'est jamais modifié.
     *
     * @return string statut final du paiement (success|failed|pending)
     */
    private function applyVerifiedPayPlusStatus(Payment $payment, object $response, string $via): string
    {
        $payplusStatus = $response->status ?? $response->response_text ?? null;

        return DB::transaction(function () use ($payment, $response, $payplusStatus, $via) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === 'success') {
                return 'success';
            }

            if ($payplusStatus === 'completed') {
                $payment->update([
                    'status'   => 'success',
                    'paid_at'  => now(),
                    'metadata' => array_merge((array) ($payment->metadata ?? []), [
                        'verified_via' => $via,
                        'payplus_response' => (array) $response,
                    ]),
                ]);

                $this->syncDerivedAmounts($payment);

                if ($payment->student) {
                    Notification::paymentReceived($payment);
                }

                Log::info('Payment completed', [
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'verified_via' => $via,
                ]);

                return 'success';
            }

            if ($payplusStatus === 'notcompleted' && $payment->status === 'pending') {
                $payment->update([
                    'status'   => 'failed',
                    'metadata' => array_merge((array) ($payment->metadata ?? []), [
                        'verified_via' => $via,
                        'error' => $response->response_text ?? 'Paiement non complété',
                        'payplus_response' => (array) $response,
                    ]),
                ]);

                if ($payment->student) {
                    Notification::paymentFailed($payment);
                }

                return 'failed';
            }

            return $payment->status;
        });
    }

    /**
     * List all payments (admin)
     * Route: GET /api/payments
     */
    public function index(Request $request)
    {
        if ($request->attributes->get('school_year_available') === false) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => (int) $request->input('per_page', 20),
                'total' => 0,
            ], 200);
        }

        // Le scope global de Payment restreint aux annexes accessibles (institution incluse)
        $query = Payment::with(['student.annexe', 'paymentLink', 'installment'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('payment_link_id')) {
            $query->where('payment_link_id', $request->payment_link_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->filled('annexe_id')) {
            $query->whereHas('student', fn ($q) => $q->where('annexe_id', $request->annexe_id));
        }
        if ($request->filled('school_year')) {
            $query->whereHas('paymentLink', fn ($q) => $q->where('school_year', $request->school_year));
        }
        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%{$s}%")
                  ->orWhere('payer_name', 'like', "%{$s}%")
                  ->orWhere('payer_phone', 'like', "%{$s}%")
                  ->orWhere('payplus_transaction_id', 'like', "%{$s}%")
                  ->orWhereHas('student', fn ($sq) =>
                      $sq->where('first_name', 'like', "%{$s}%")
                         ->orWhere('last_name', 'like', "%{$s}%")
                         ->orWhere('matricule', 'like', "%{$s}%")
                  );
            });
        }

        return response()->json($query->paginate($request->input('per_page', 20)));
    }

    /**
     * Admin: force-update payment status
     * Route: PATCH /api/admin/payments/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $v = $request->validate([
            'status' => 'required|in:success,failed,pending',
            'note'   => 'nullable|string|max:500',
        ]);

        $oldStatus = $payment->status;

        DB::transaction(function () use ($payment, $v, $oldStatus) {
            $payment->update([
                'status'   => $v['status'],
                'paid_at'  => $v['status'] === 'success' ? ($payment->paid_at ?? now()) : null,
                'metadata' => array_merge((array)($payment->metadata ?? []), [
                    'admin_note'  => $v['note'] ?? null,
                    'force_updated_by' => auth()->id(),
                    'force_updated_at' => now()->toISOString(),
                ]),
            ]);

            // Recalculer les agrégats si le statut a changé
            if ($oldStatus !== $v['status']) {
                $this->syncDerivedAmounts($payment);
            }
        });

        return response()->json([
            'message' => 'Payment status updated',
            'payment' => $payment->fresh()->load(['student', 'paymentLink', 'installment']),
        ]);
    }

    /**
     * GET /api/admin/payments/recent
     * Les N derniers paiements pour le widget dashboard, scopé par rôle.
     */
    public function recent(Request $request)
    {
        if ($request->attributes->get('school_year_available') === false) {
            return response()->json(['data' => []], 200);
        }

        // Même scope que index() (scope global Payment)
        $query = Payment::with(['student.annexe', 'paymentLink', 'installment'])
            ->orderByDesc('created_at');

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }
        // Type via paymentLink.type (tuition | registration | other)
        if ($request->filled('type')) {
            $query->whereHas('paymentLink', fn ($q) => $q->where('type', $request->type));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        // Filtre par annexe (pour super_admin_institution)
        if ($request->filled('annexe_id')) {
            $query->whereHas('student', fn ($q) => $q->where('annexe_id', $request->annexe_id));
        }
        if ($request->filled('school_year')) {
            $query->whereHas('paymentLink', fn ($q) => $q->where('school_year', $request->school_year));
        }

        $limit    = min((int) $request->get('limit', 5), 20);
        $payments = $query->limit($limit)->get();

        return response()->json(['data' => $payments]);
    }

    /**
     * Show a single payment (admin)
     * Route: GET /api/admin/payments/{id}
     */
    public function show($id)
    {
        $payment = Payment::with(['student', 'paymentLink', 'installment'])->findOrFail($id);

        return response()->json($payment);
    }

    /**
     * Créer un checkout public pour un payment link
     * Route: POST /api/payments/public/checkout
     */
    public function publicCheckout(Request $request)
    {
        $validated = $request->validate([
            'payment_link_id' => 'sometimes|exists:payment_links,id',
            'token'           => 'sometimes|string',
            'amount'          => 'required|numeric|min:1',
            'method'          => 'required|string|in:mtn,moov',
            'payer_phone'     => 'required|string|max:20',
            'payer_email'     => 'sometimes|nullable|email',
            'payer_first_name'=> 'sometimes|nullable|string|max:100',
            'payer_last_name' => 'sometimes|nullable|string|max:100',
            'matricule'       => 'sometimes|string|max:50',
        ]);

        // Récupérer le lien de paiement
        if (!empty($validated['payment_link_id'])) {
            $link = PaymentLink::findOrFail($validated['payment_link_id']);
        } elseif (!empty($validated['token'])) {
            $link = PaymentLink::where('token', $validated['token'])->firstOrFail();
        } else {
            return response()->json(['message' => 'payment_link_id ou token requis'], 422);
        }

        if (!$link->isValid()) {
            return response()->json(['message' => 'Ce lien de paiement est inactif, expiré ou a déjà été utilisé.'], 422);
        }

        // Le montant ne peut pas dépasser le restant dû sur le lien
        $alreadyPaid = (float) Payment::query()
            ->where('payment_link_id', $link->id)
            ->where('status', 'success')
            ->sum('amount');
        $remaining = max(0, (float) $link->amount - $alreadyPaid);

        if ((float) $validated['amount'] > $remaining) {
            return response()->json([
                'message' => 'Le montant dépasse le restant dû sur ce lien (' . $remaining . ').',
            ], 422);
        }

        // Déterminer l'étudiant associé
        $studentId = $link->student_id;
        if (!$studentId && !empty($validated['matricule'])) {
            $student = Student::where('matricule', $validated['matricule'])->first();
            if (!$student) {
                return response()->json(['message' => 'Étudiant introuvable pour ce matricule.'], 404);
            }
            $studentId = $student->id;
        }

        // Échéance ciblée : la première tranche non soldée du lien
        $installment = $link->installments()
            ->where('status', 'active')
            ->whereRaw('amount_paid < amount')
            ->orderBy('tranche_number')
            ->first()
            ?? $link->installments()->orderBy('tranche_number')->first();

        // Créer l'enregistrement Payment (status pending)
        $payment = Payment::create([
            'student_id'     => $studentId,
            'payment_link_id'=> $link->id,
            'installment_id' => $installment?->id,
            'amount'         => $validated['amount'],
            'method'         => $validated['method'],
            'payer_name'     => trim(($validated['payer_first_name'] ?? '') . ' ' . ($validated['payer_last_name'] ?? '')) ?: null,
            'payer_email'    => $validated['payer_email'] ?? null,
            'payer_phone'    => $validated['payer_phone'],
            'reference'      => 'PAY-' . strtoupper(Str::random(10)),
            'status'         => 'pending',
        ]);

        // Lancer le paiement mobile money via PayPlus (sans redirection)
        try {
            $result = $this->payplusService->launchPayment($payment, $validated['payer_phone'], [
                'payer_first_name' => $validated['payer_first_name'] ?? null,
                'payer_last_name'  => $validated['payer_last_name']  ?? null,
                'payer_email'      => $validated['payer_email']      ?? null,
            ]);
        } catch (\Exception $e) {
            // Supprimer le paiement pending créé car l'initiation a échoué
            $payment->delete();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Paiement initié. Veuillez confirmer depuis votre téléphone mobile.',
            'reference'     => $payment->reference,
            'payplus_token' => $result['token'],
        ], 201);
    }

    /**
     * Vérifier le statut d'un paiement auprès de PayPlus (polling depuis le front)
     * Route: GET /api/payments/check/{reference}
     */
    public function checkStatus(string $reference)
    {
        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return response()->json(['message' => 'Paiement introuvable.'], 404);
        }

        // Déjà finalisé — pas besoin d'appeler PayPlus
        if (in_array($payment->status, ['success', 'failed'])) {
            return response()->json(['status' => $payment->status, 'reference' => $reference]);
        }

        // Pas de token PayPlus encore — toujours en attente d'initiation
        if (!$payment->payplus_transaction_id) {
            return response()->json(['status' => 'pending', 'reference' => $reference]);
        }

        try {
            $response = $this->payplusService->verify($payment->payplus_transaction_id);

            Log::info('PayPlus verify response', [
                'reference' => $reference,
                'response' => (array) $response,
            ]);

            $status = $this->applyVerifiedPayPlusStatus($payment, $response, 'polling');

            return response()->json(['status' => $status, 'reference' => $reference]);

        } catch (\Exception $e) {
            Log::error('checkStatus error', ['reference' => $reference, 'error' => $e->getMessage()]);
            return response()->json(['status' => 'pending', 'reference' => $reference]);
        }
    }

    /**
     * Retour PayPlus après paiement (return_url)
     * Route: GET /api/payplus/return?token=xxx
     */
    public function payplusReturn(Request $request)
    {
        try {
            $token = $request->get('token');

            if (!$token) {
                return redirect(env('FRONT_FAILED_URL') . '?error=missing_token');
            }

            // Retrouver le paiement par token PayPlus
            $payment = Payment::where('payplus_transaction_id', $token)->first();

            if (!$payment) {
                Log::error('Payment not found for PayPlus token: ' . $token);
                return redirect(env('FRONT_FAILED_URL') . '?error=payment_not_found');
            }

            // Vérifier le statut auprès de PayPlus
            $status = $this->applyVerifiedPayPlusStatus($payment, $this->payplusService->verify($token), 'return_url');

            // Redirection selon le statut
            if ($status === 'success') {
                return redirect(env('FRONT_SUCCESS_URL') . '?reference=' . $payment->reference);
            }

            return redirect(env('FRONT_FAILED_URL') . '?reference=' . $payment->reference . '&status=' . $status);

        } catch (\Exception $e) {
            Log::error('PayPlus return error: ' . $e->getMessage(), [
                'token' => $request->get('token'),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect(env('FRONT_FAILED_URL') . '?error=system_error');
        }
    }

    /**
     * Webhook PayPlus (callback_url)
     * Route: POST /api/payplus/webhook
     *
     * Le contenu du webhook n'est PAS considéré comme fiable (aucune signature) :
     * il sert uniquement de déclencheur. Le statut réel est toujours re-vérifié
     * auprès de l'API PayPlus avant toute modification.
     */
    public function payplusWebhook(Request $request)
    {
        Log::info('PayPlus webhook received', $request->all());

        $token = $request->input('token');

        if (!$token || !is_string($token)) {
            return response()->json(['message' => 'Token missing'], 400);
        }

        $payment = Payment::where('payplus_transaction_id', $token)->first();

        if (!$payment) {
            Log::error('Payment not found for webhook token: ' . $token);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        try {
            $response = $this->payplusService->verify($token);
        } catch (\Throwable $e) {
            Log::error('PayPlus webhook: verification failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            // Code 5xx pour que PayPlus retente l'envoi plus tard
            return response()->json(['message' => 'Verification unavailable'], 502);
        }

        try {
            $status = $this->applyVerifiedPayPlusStatus($payment, $response, 'webhook');

            return response()->json(['message' => 'Webhook processed', 'status' => $status], 200);

        } catch (\Throwable $e) {
            Log::error('PayPlus webhook error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['message' => 'Webhook error'], 500);
        }
    }
}
