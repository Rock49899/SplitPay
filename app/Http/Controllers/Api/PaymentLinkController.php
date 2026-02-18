<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StorePaymentLinkRequest;
use App\Models\PaymentLink;
use App\Models\Student;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentLinkMail;


class PaymentLinkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['publicShow']);
    }

    // Liste des liens — si student_id est fourni, ne retourner que les liens de cet étudiant
    public function index(Request $request)
    {
        $perPage = (int)$request->get('per_page', 15);
        $query = PaymentLink::query();

       
        if ($studentId = $request->get('student_id')) {
            $query->where(function($q) use ($studentId) {
                // direct student_id sur la table payment_links
                $q->where('student_id', $studentId);
                // ou via student_fee_item -> student
                $q->orWhereHas('studentFeeItem', function ($qq) use ($studentId) {
                    $qq->where('student_id', $studentId);
                });
            });
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

        // si c'est scolarité, on plafonne au restant dû
        if ($type === 'tuition') {
            $remaining = max(0, $student->tuition_amount - $student->amount_paid);

            if ($remaining <= 0) {
                return response()->json([
                    'message' => 'This student has already fully paid tuition.'
                ], 422);
            }

            $amount = min($v['amount'], $remaining);
        } else {
            $amount = $v['amount'];
        }

        // currency : utiliser ce que fournit le client ou USD par défaut
        $currency = $v['currency'] ?? 'USD';

        $link = PaymentLink::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
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

        return response()->json([
            'message' => 'Payment link created successfully',
            'payment_link' => $link
        ], 201);
    }

	// envoie du lien de paiement par email (admin)
	public function sendByEmail(Request $request, $id)
	{
		$request->validate([
			'email' => 'sometimes|email'
		]);

		try {
			$link = PaymentLink::findOrFail($id);
			// obtenir email cible : priorité param, sinon email étudiant lié
			$target = $request->input('email') ?? $link->student?->email ?? null;
			if (!$target) {
				return response()->json(['message' => 'No target email available'], 422);
			}

			// envoyer le mail
			Mail::to($target)->send(new PaymentLinkMail($link));

			// marquer sent_at si besoin
			try { $link->markAsSent(); } catch (\Throwable $e) { /* non bloquant */ }

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

    return response()->json($link->load('installments'), 200);
    }

    // public function update(Request $request, $id)
    // {
    //     $link = PaymentLink::findOrFail($id);
    //     $v = $request->only(['title','description','amount','currency','expires_at','is_active']);
    //     $link->update($v);
    //     return response()->json(['message'=>'Payment link updated','payment_link'=>$link], 200);
    // }


    public function update(Request $request, PaymentLink $paymentLink)
{
    $validated = $request->validate([
        'amount'      => 'sometimes|numeric|min:0',
        'currency'    => 'sometimes|string|max:10',
        'description' => 'nullable|string',
        'type'        => 'sometimes|string', 
        'due_date'    => 'nullable|date',
        'expire_at'   => 'nullable|date',
        'status' => 'sometimes|in:pending,paid,expired,cancelled'
    ]);

    $paymentLink->update($validated);

    return response()->json($paymentLink);
}


    public function destroy($id)
    {
        $link = PaymentLink::findOrFail($id);
        $link->delete();
        return response()->json(['message'=>'Payment link deleted'], 200);
    }
}
