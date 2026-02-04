<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StorePaymentLinkRequest;
use App\Models\PaymentLink;

class PaymentLinkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['publicShow']);
    }

    public function index(Request $request)
    {
        $perPage = (int)$request->get('per_page', 15);
        $query = PaymentLink::query();
        if ($q = $request->get('q')) $query->where('title','like',"%{$q}%");
        return response()->json($query->orderByDesc('created_at')->paginate($perPage), 200);
    }

    public function store(StorePaymentLinkRequest $request)
    {
        $v = $request->validated();
        $link = PaymentLink::create([
            'id' => (string) Str::uuid(),
            'title' => $v['title'],
            'description' => $v['description'] ?? null,
            'amount' => $v['amount'],
            'currency' => strtoupper($v['currency']),
            'expires_at' => $v['expires_at'] ?? null,
            'annexe_id' => $v['annexe_id'],
            'public_token' => Str::random(40),
            'is_active' => true,
        ]);
        return response()->json(['message'=>'Payment link created','payment_link'=>$link], 201);
    }

    public function show($id)
    {
        $link = PaymentLink::findOrFail($id);
        return response()->json($link, 200);
    }

    // public page by token (no auth)
    public function publicShow($token)
    {
        $link = PaymentLink::where('public_token', $token)->where('is_active', true)->firstOrFail();
        return response()->json($link, 200);
    }

    public function update(Request $request, $id)
    {
        $link = PaymentLink::findOrFail($id);
        $v = $request->only(['title','description','amount','currency','expires_at','is_active']);
        $link->update($v);
        return response()->json(['message'=>'Payment link updated','payment_link'=>$link], 200);
    }

    public function destroy($id)
    {
        $link = PaymentLink::findOrFail($id);
        $link->delete();
        return response()->json(['message'=>'Payment link deleted'], 200);
    }
}
