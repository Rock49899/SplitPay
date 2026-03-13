<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StorePaymentLinkRequest;
use App\Models\PaymentLink;
use App\Models\Student;
use App\Models\Annexe;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentLinkMail;


class PaymentLinkController extends Controller
{
    use FiltersByAnnexe;

    private function currentSchoolYear(): string
    {
        $now = now();
        $base = $now->month >= 9 ? $now->year : $now->year - 1;
        return $base . '-' . ($base + 1);
    }
    
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['publicShow']);
    }

    // Liste des liens — si student_id est fourni, ne retourner que les liens de cet étudiant
    public function index(Request $request)
    {
        if ($request->attributes->get('school_year_available') === false) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => (int) $request->get('per_page', 15),
                'total' => 0,
            ], 200);
        }

        $perPage = (int)$request->get('per_page', 15);
        $query = PaymentLink::query();

        // IMPORTANT: Filtrer par annexe de l'utilisateur via la relation student
        if (!$this->isSuperAdminInstitution()) {
            $annexeIds = $this->getUserAnnexeIds();
            if (empty($annexeIds)) {
                return response()->json(['data' => [], 'total' => 0], 200);
            }
            $query->whereHas('student', fn ($q) => $q->whereIn('annexe_id', $annexeIds));
        }
       
        if ($studentId = $request->get('student_id')) {
            $query->where(function($q) use ($studentId) {
                // direct student_id sur la table payment_links
                $q->where('student_id', $studentId);
            });
        }

        if ($schoolYear = $request->get('school_year')) {
            $query->where('school_year', $schoolYear);
        }

        // recherche
        if ($q = $request->get('q')) {
            $query->where(function ($qq) use ($q) {
                $qq->where('token', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%");
            });
        }

        // eager load des relations utiles pour l'affichage (installments -> payments, payments directs, student)
        $query->with(['installments.payments', 'student']);

        $links = $query->orderByDesc('created_at')->paginate($perPage);
        return response()->json($links, 200);
    }

   public function store(StorePaymentLinkRequest $request)
{
    $v = $request->validated();

    $type = $v['type'] ?? 'tuition';
    $student = Student::findOrFail($v['student_id']);
    $schoolYear = $v['school_year']
        ?? $request->input('school_year')
        ?? $student->currentEnrollment?->school_year
        ?? $this->currentSchoolYear();

    // SECURITE: Vérifier que l'utilisateur a accès à cet étudiant (même annexe)
    if (!$this->userHasAccessToAnnexe($student->annexe_id)) {
        return response()->json([
            'message' => 'You do not have permission to create payment links for students from other annexes.'
        ], 403);
    }

    // si c'est scolarité, on plafonne au restant dû sur l'enrollment actif
    if ($type === 'tuition') {
        $enrollment = $student->enrollments()
            ->where('school_year', $schoolYear)
            ->orderByDesc('school_year')
            ->first();

        if ($enrollment) {
            $remaining = max(0, (float) $enrollment->tuition_amount - (float) $enrollment->amount_paid);
            if ($remaining <= 0) {
                return response()->json([
                    'message' => 'This student has already fully paid tuition.'
                ], 422);
            }
            $amount = min($v['amount'], $remaining);
        } else {
            // Pas d'enrollment actif — on accepte le montant tel quel
            $amount = $v['amount'];
        }
    } else {
        $amount = $v['amount'];
    }

    $currency = $v['currency'] ?? 'USD';

    // 1️Création du PaymentLink
    $link = PaymentLink::create([
        'id' => (string) Str::uuid(),
        'student_id' => $student->id,
        'school_year' => $schoolYear,
        'type' => $type,
        'token' => PaymentLink::generateUniqueToken(),
        'amount' => $amount,
        'currency' => $currency,
        'description' => $v['description'] ?? null,
        'due_date' => $v['due_date'] ?? null,
        'expire_at' => $v['expire_at'] ?? null,
        'status' => 'active',
        'created_by' => auth()->id(),
    ]);

    // 2️Création automatique de l'Installment correspondant
    $installment = $link->installments()->create([
        'id' => (string) Str::uuid(),
        'payment_link_id' => $link->id,
        'amount' => $amount,
        'amount_paid' => 0,
        'due_date' => $v['due_date'] ?? null,
        'status' => 'active',
        'tranche_number' => $v['tranche_number'] ?? 1,
    ]);

    return response()->json([
        'message' => 'Payment link created successfully with installment',
        'payment_link' => $link->load('installments')
    ], 201);
}


	// envoie du lien de paiement par email (admin)
	public function sendByEmail(Request $request, $id)
	{
		$request->validate([
			'email' => 'sometimes|email',
			'message_type' => 'sometimes|in:initial,reminder,urgent,final'
		]);

		try {
			$link = PaymentLink::findOrFail($id);
			// obtenir email cible : priorité param, sinon email étudiant lié
			$target = $request->input('email') ?? $link->student?->email ?? null;
			if (!$target) {
				return response()->json(['message' => 'No target email available'], 422);
			}

			// envoyer le mail avec le type de message
			$messageType = $request->input('message_type', 'initial');
			Mail::to($target)->send(new PaymentLinkMail($link, $messageType));

			// marquer sent_at si besoin
			try { $link->markAsSent(); } catch (\Throwable $e) { /* non bloquant */ }

			// Tracker l'envoi manuel sur les installments actifs
			if (in_array($messageType, ['reminder', 'urgent', 'final'])) {
				$link->activeInstallments()->each(function ($installment) {
					$installment->update([
						'last_reminder_sent_at' => now(),
						'reminder_count' => $installment->reminder_count + 1,
					]);
				});
			}

			return response()->json(['message' => 'Payment link sent', 'email' => $target], 200);
		} catch (\Throwable $e) {
			\Log::error('PaymentLinkController@sendByEmail failed', [
				'error' => $e->getMessage(),
  			'trace' => $e->getTraceAsString(),
				'id' => $id,
				'payload' => $request->all(),
			]);
			return response()->json(['message' => 'Failed to send payment link'], 500);
		}
   }



    public function show($id)
    {
        $link = PaymentLink::findOrFail($id);
        return response()->json($link, 200);
    }

    // public page by token (no auth)
    public function publicShow($token)
   {
    $link = PaymentLink::where('token', $token)
        ->where('status', 'active')
        ->firstOrFail();

    return response()->json($link->load(['student', 'installments.payments', 'payments']), 200);
    }



    public function update(Request $request, PaymentLink $paymentLink)
{
    $validated = $request->validate([
        'amount'      => 'sometimes|numeric|min:0',
        'currency'    => 'sometimes|string|max:10',
        'description' => 'nullable|string',
        'type'        => 'sometimes|string', 
        'due_date'    => 'nullable|date',
        'expire_at'   => 'nullable|date',
        'status' => 'sometimes|in:active,used,expired'  // Fixed: match database enum
    ]);

    $paymentLink->update($validated);

    return response()->json($paymentLink->fresh());
}


    public function destroy($id)
    {
        $link = PaymentLink::findOrFail($id);
        $link->delete();
        return response()->json(['message'=>'Payment link deleted'], 200);
    }

    /**
     * Broadcast (create) payment links for multiple students at once.
      */
    public function broadcast(Request $request)
    {
        $v = $request->validate([
            'target'            => 'required|in:all,annexe,specialization',
            'annexe_id'         => 'required_if:target,annexe|nullable|exists:annexes,id',
            'specialization_id' => 'required_if:target,specialization|nullable|exists:specializations,id',
            'school_year'       => ['nullable', 'string', 'max:20', 'regex:/^\\d{4}-\\d{4}$/'],
            'amount'      => 'required|numeric|min:1',
            'currency'    => 'nullable|string|max:10',
            'type'        => 'nullable|string|in:tuition,registration,other',
            'description' => 'nullable|string|max:500',
            'due_date'    => 'nullable|date',
            'expire_at'   => 'nullable|date',
            'send_email'  => 'nullable|boolean',
        ]);

        $query = Student::query();
        $schoolYear = !empty($v['school_year'])
            ? $v['school_year']
            : ($request->input('school_year') ?: $this->currentSchoolYear());

        if ($v['target'] === 'annexe') {
            $query->where('annexe_id', $v['annexe_id']);
        } elseif ($v['target'] === 'specialization') {
            $query->where('specialization_id', $v['specialization_id']);
            if (!empty($v['annexe_id'])) {
                $query->where('annexe_id', $v['annexe_id']);
            }
        }
        if (!empty($schoolYear)) {
            $query->whereHas('enrollments', fn ($q) => $q->where('school_year', $schoolYear));
        }

        $students = $query->get();

        if ($students->isEmpty()) {
            return response()->json(['message' => 'No students found for the selected target'], 422);
        }

        $created = 0;
        $sent    = 0;
        $errors  = [];

        DB::transaction(function () use ($students, $v, $schoolYear, &$created, &$sent, &$errors) {
            foreach ($students as $student) {
                try {
                    $link = PaymentLink::create([
                        'id'          => (string) Str::uuid(),
                        'student_id'  => $student->id,
                        'school_year' => $schoolYear,
                        'type'        => $v['type'] ?? 'tuition',
                        'token'       => PaymentLink::generateUniqueToken(),
                        'amount'      => $v['amount'],
                        'currency'    => $v['currency'] ?? 'XOF',
                        'description' => $v['description'] ?? null,
                        'due_date'    => $v['due_date'] ?? null,
                        'expire_at'   => $v['expire_at'] ?? null,
                        'status'      => 'active',
                        'created_by'  => auth()->id(),
                    ]);

                    $link->installments()->create([
                        'id'             => (string) Str::uuid(),
                        'payment_link_id'=> $link->id,
                        'amount'         => $v['amount'],
                        'amount_paid'    => 0,
                        'due_date'       => $v['due_date'] ?? null,
                        'status'         => 'active',
                        'tranche_number' => 1,
                    ]);

                    $created++;

                    if (!empty($v['send_email']) && $student->email) {
                        try {
                            Mail::to($student->email)->send(new PaymentLinkMail($link));
                            $sent++;
                        } catch (\Throwable $e) {
                            $errors[] = "Email failed for {$student->email}: " . $e->getMessage();
                        }
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Failed for student {$student->id}: " . $e->getMessage();
                }
            }
        });

        return response()->json([
            'message'  => "Broadcast complete: {$created} links created, {$sent} emails sent.",
            'created'  => $created,
            'sent'     => $sent,
            'errors'   => $errors,
            'total'    => $students->count(),
        ], 201);
    }
}
