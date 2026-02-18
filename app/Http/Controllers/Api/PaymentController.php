<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Installment;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['publicCreate']);
    }

    public function index(Request $request)
    {
        $perPage = (int)$request->get('per_page', 15);
        $query = Payment::query();
        if ($q = $request->get('q')) $query->where('reference','like',"%{$q}%");
        return response()->json($query->orderByDesc('created_at')->paginate($perPage), 200);
    }

    // point d'entrée public pour créer un paiement via un lien (sans authentification)
    public function publicCreate(StorePaymentRequest $request)
    {
        $v = $request->validated();
        $link = PaymentLink::findOrFail($v['payment_link_id']);
        return $this->store($request, $link);
    }

    // protected function store(Request $request, $link = null)
    // {
    //     $v = $request->validated();
    //     return DB::transaction(function() use ($v, $link, $request) {
    //         $link = $link ?: PaymentLink::findOrFail($v['payment_link_id']);
    //         if (!empty($v['installment_id'])) {
    //             $inst = Installment::findOrFail($v['installment_id']);
    //         }

    //         $payment = Payment::create([
    //             'id' => (string) Str::uuid(),
    //             'payment_link_id' => $link->id,
    //             'installment_id' => $v['installment_id'] ?? null,
    //             'amount' => $v['amount'],
    //             'method' => $v['method'],
    //             'metadata' => $v['metadata'] ?? null,
    //             'reference' => Str::upper(Str::random(12)),
    //             'status' => 'completed', // placeholder : en production, intégrer le prestataire et définir le statut selon sa réponse
    //         ]);

    //         // marquer l'échéance payée si applicable
    //         if (!empty($v['installment_id']) && isset($inst)) {
    //             $inst->update(['is_paid' => true]);
    //         }

    //         return response()->json(['message'=>'Payment recorded','payment'=>$payment], 201);
    //     });
    // }

    protected function store(StorePaymentRequest $request)
{
    $v = $request->validated();

    return DB::transaction(function () use ($v) {

        $link = PaymentLink::where('token', $v['token'])->firstOrFail();

        if (!$link->isValid()) {
            abort(422, 'Payment link is not valid.');
        }

        $installment = Installment::findOrFail($v['installment_id']);

        // sécurité : cohérence
        if ($installment->payment_link_id !== $link->id) {
            abort(422, 'Installment does not belong to this payment link.');
        }

        if ($installment->remaining_amount <= 0) {
            abort(422, 'Installment already fully paid.');
        }

        if ($v['amount'] > $installment->remaining_amount) {
            abort(422, 'Amount exceeds remaining balance.');
        }

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'installment_id' => $installment->id,
            'reference' => Str::upper(Str::random(12)),
            'amount' => $v['amount'],
            'method' => $v['method'],
            'status' => 'success',
            'payment_date' => now(),
        ]);

        $installment->recordPayment($v['amount']);

        // Si scolarité, incrément student
        if ($link->type === 'tuition' && $link->student) {
            $link->student->recordPayment($v['amount']);
        }

        // Si tout est soldé, fermer le lien
        if ($link->remainingAmount() <= 0) {
            $link->markAsUsed();
        }

        return response()->json([
            'message' => 'Payment successful',
            'payment' => $payment
        ], 201);
    });
    }


    public function show($id)
    {
        $payment = Payment::findOrFail($id);
        return response()->json($payment, 200);
    }
}
