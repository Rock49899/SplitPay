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

    // public entrypoint to create payment for public links (no auth)
    public function publicCreate(StorePaymentRequest $request)
    {
        $v = $request->validated();
        $link = PaymentLink::findOrFail($v['payment_link_id']);
        return $this->store($request, $link);
    }

    // internal create (optionally called by publicCreate)
    protected function store(Request $request, $link = null)
    {
        $v = $request->validated();
        return DB::transaction(function() use ($v, $link, $request) {
            $link = $link ?: PaymentLink::findOrFail($v['payment_link_id']);
            if (!empty($v['installment_id'])) {
                $inst = Installment::findOrFail($v['installment_id']);
            }

            $payment = Payment::create([
                'id' => (string) Str::uuid(),
                'payment_link_id' => $link->id,
                'installment_id' => $v['installment_id'] ?? null,
                'amount' => $v['amount'],
                'method' => $v['method'],
                'metadata' => $v['metadata'] ?? null,
                'reference' => Str::upper(Str::random(12)),
                'status' => 'completed', // stub: in real integrate provider
            ]);

            // mark installment paid if relevant
            if (!empty($v['installment_id']) && isset($inst)) {
                $inst->update(['is_paid' => true]);
            }

            return response()->json(['message'=>'Payment recorded','payment'=>$payment], 201);
        });
    }

    public function show($id)
    {
        $payment = Payment::findOrFail($id);
        return response()->json($payment, 200);
    }
}
