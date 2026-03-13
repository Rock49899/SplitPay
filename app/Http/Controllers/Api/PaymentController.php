<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

        $query = Payment::with(['student.annexe', 'paymentLink', 'installment'])
            ->orderByDesc('created_at');

        // IMPORTANT: Filtrer par annexe de l'utilisateur via la relation student
        if (!$this->isSuperAdminInstitution()) {
            $annexeIds = $this->getUserAnnexeIds();
            if (empty($annexeIds)) {
                return response()->json(['data' => [], 'total' => 0], 200);
            }
            $query->whereHas('student', fn ($q) => $q->whereIn('annexe_id', $annexeIds));
        }

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

            // If forcing to success from a non-success state: increment amount_paid
            if ($v['status'] === 'success' && $oldStatus !== 'success') {
                $payment->student?->increment('amount_paid', $payment->amount);
                if ($payment->installment_id) {
                    $inst = $payment->installment;
                    if ($inst) {
                        $inst->increment('amount_paid', $payment->amount);
                        if ($inst->amount_paid >= $inst->amount) {
                            $inst->update(['status' => 'paid']);
                        }
                    }
                }
                $link = $payment->paymentLink;
                if ($link && $payment->amount >= $link->amount) {
                    $link->update(['status' => 'used']);
                }
            }

            // If reverting from success: decrement
            if ($oldStatus === 'success' && $v['status'] !== 'success') {
                $payment->student?->decrement('amount_paid', $payment->amount);
                if ($payment->installment_id) {
                    $inst = $payment->installment;
                    if ($inst) {
                        $inst->decrement('amount_paid', $payment->amount);
                        $inst->update(['status' => 'active']);
                    }
                }
                $link = $payment->paymentLink;
                if ($link) {
                    $link->update(['status' => 'active']);
                }
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

        $query = Payment::with(['student.annexe', 'paymentLink', 'installment'])
            ->orderByDesc('created_at');

        // Même scope que index()
        if (!$this->isSuperAdminInstitution()) {
            $annexeIds = $this->getUserAnnexeIds();
            if (empty($annexeIds)) {
                return response()->json(['data' => []], 200);
            }
            $query->whereHas('student', fn ($q) => $q->whereIn('annexe_id', $annexeIds));
        }

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
     * Route: GET /api/payments/{payment}
     */
    public function show(Payment $payment)
    {
        return response()->json($payment->load(['student', 'paymentLink', 'installment']));
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

        if ($link->status !== 'active') {
            return response()->json(['message' => 'Ce lien de paiement est inactif ou a déjà été utilisé.'], 422);
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

        // Créer l'enregistrement Payment (status pending)
        $payment = Payment::create([
            'student_id'     => $studentId,
            'payment_link_id'=> $link->id,
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
                'response' => is_object($response) ? (array) $response : $response,
            ]);

            $payplusStatus = $response->status ?? $response->response_text ?? null;

            if ($payplusStatus === 'completed') {
                DB::transaction(function () use ($payment) {
                    if ($payment->status === 'success') return; // déjà traité

                    $payment->update([
                        'status'  => 'success',
                        'paid_at' => now(),
                        'metadata' => array_merge((array)($payment->metadata ?? []), ['verified_via' => 'polling']),
                    ]);

                    // Mettre à jour student.amount_paid
                    $student = $payment->student;
                    if ($student) {
                        $student->increment('amount_paid', $payment->amount);
                    }

                    // Mettre à jour installment.amount_paid
                    if ($payment->installment_id) {
                        $inst = $payment->installment;
                    } else {
                        $inst = $payment->paymentLink?->installments()->first();
                    }
                    if ($inst) {
                        $inst->increment('amount_paid', $payment->amount);
                        if ($inst->amount_paid >= $inst->amount) {
                            $inst->update(['status' => 'paid']);
                        }
                    }

                    // Marquer lien utilisé si besoin
                    $link = $payment->paymentLink;
                    if ($link && $payment->amount >= $link->amount) {
                        $link->update(['status' => 'used']);
                    }

                    // CRÉER NOTIFICATION DE SUCCÈS
                    \App\Models\Notification::paymentReceived($payment);
                });

                return response()->json(['status' => 'success', 'reference' => $reference]);
            }

            return response()->json(['status' => 'pending', 'reference' => $reference]);

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
            $response = $this->payplusService->verify($token);

            DB::transaction(function () use ($payment, $response) {
                
                if (isset($response->status) && $response->status === 'completed') {
                    
                    // Paiement réussi
                    $payment->update([
                        'status' => 'success',
                        'paid_at' => now(),
                        'metadata' => (array) $response,
                    ]);

                    // Mettre à jour le solde de l'étudiant
                    $student = $payment->student;
                    if ($student) {
                        $student->increment('amount_paid', $payment->amount);
                    }

                    // Mettre à jour installment.amount_paid
                    if ($payment->installment_id) {
                        $installment = $payment->installment;
                        if ($installment) {
                            $installment->increment('amount_paid', $payment->amount);
                            if ($installment->amount_paid >= $installment->amount) {
                                $installment->update(['status' => 'paid']);
                            }
                        }
                    } else {
                        // Pas d'installment direct, chercher via payment_link
                        $link = $payment->paymentLink;
                        if ($link) {
                            $inst = $link->installments()->first();
                            if ($inst) {
                                $inst->increment('amount_paid', $payment->amount);
                                if ($inst->amount_paid >= $inst->amount) {
                                    $inst->update(['status' => 'paid']);
                                }
                            }
                        }
                    }

                    // Marquer le lien comme utilisé si montant complet
                    $link = $payment->paymentLink;
                    if ($link && $payment->amount >= $link->amount) {
                        $link->update(['status' => 'used']);
                    }

                    Log::info('Payment completed successfully', [
                        'payment_id' => $payment->id,
                        'reference' => $payment->reference,
                        'amount' => $payment->amount
                    ]);

                } else {
                    
                    // Paiement échoué
                    $payment->update([
                        'status' => 'failed',
                        'metadata' => ['error' => $response->response_text ?? 'Paiement non complété', 'response' => (array) $response],
                    ]);

                    Log::warning('Payment failed', [
                        'payment_id' => $payment->id,
                        'reference' => $payment->reference,
                        'response' => $response
                    ]);
                }
            });

            // Redirection selon le statut
            if ($payment->status === 'success') {
                return redirect(env('FRONT_SUCCESS_URL') . '?reference=' . $payment->reference);
            } else {
                return redirect(env('FRONT_FAILED_URL') . '?reference=' . $payment->reference);
            }

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
     * IMPORTANT: Désactiver CSRF pour cette route
     */
    public function payplusWebhook(Request $request)
    {
        try {
            Log::info('PayPlus webhook received', $request->all());

            $token = $request->input('token');
            $responseCode = $request->input('response_code');

            if (!$token) {
                return response()->json(['message' => 'Token missing'], 400);
            }

            // Retrouver le paiement
            $payment = Payment::where('payplus_transaction_id', $token)->first();

            if (!$payment) {
                Log::error('Payment not found for webhook token: ' . $token);
                return response()->json(['message' => 'Payment not found'], 404);
            }

            DB::transaction(function () use ($payment, $responseCode, $request) {
                
                if ($responseCode == '00') {
                    
                    // Paiement réussi
                    $payment->update([
                        'status' => 'success',
                        'paid_at' => now(),
                        'metadata' => $request->all(),
                    ]);

                    // Mettre à jour solde étudiant
                    $student = $payment->student;
                    if ($student) {
                        $student->increment('amount_paid', $payment->amount);
                    }

                    // Mettre à jour installment.amount_paid
                    if ($payment->installment_id) {
                        $installment = $payment->installment;
                        if ($installment) {
                            $installment->increment('amount_paid', $payment->amount);
                            if ($installment->amount_paid >= $installment->amount) {
                                $installment->update(['status' => 'paid']);
                            }
                        }
                    } else {
                        $pLink = $payment->paymentLink;
                        if ($pLink) {
                            $inst = $pLink->installments()->first();
                            if ($inst) {
                                $inst->increment('amount_paid', $payment->amount);
                                if ($inst->amount_paid >= $inst->amount) {
                                    $inst->update(['status' => 'paid']);
                                }
                            }
                        }
                    }

                    // Marquer lien utilisé
                    $link = $payment->paymentLink;
                    if ($link && $payment->amount >= $link->amount) {
                        $link->update(['status' => 'used']);
                    }

                    // CRÉER NOTIFICATION DE SUCCÈS
                    \App\Models\Notification::paymentReceived($payment);

                } else {
                    
                    // Paiement échoué
                    $payment->update([
                        'status' => 'failed',
                        'metadata' => ['error' => $request->input('response_text', 'Échec paiement'), 'response' => $request->all()],
                    ]);

                    // CRÉER NOTIFICATION D'ÉCHEC
                    \App\Models\Notification::paymentFailed($payment);
                }
            });

            return response()->json(['message' => 'Webhook processed'], 200);

        } catch (\Exception $e) {
            Log::error('PayPlus webhook error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['message' => 'Webhook error'], 500);
        }
    }
}