<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\StoreInstallmentRequest;
use App\Models\Installment;
use App\Models\PaymentLink;

class InstallmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $perPage = (int)$request->get('per_page', 15);
        $query = Installment::query();
        if ($link = $request->get('payment_link_id')) $query->where('payment_link_id', $link);
        return response()->json($query->orderBy('due_date')->paginate($perPage), 200);
    }

    public function store(StoreInstallmentRequest $request)
    {
        $v = $request->validated();
        PaymentLink::findOrFail($v['payment_link_id']); // ensure exists
        $inst = Installment::create([
            'id' => (string) Str::uuid(),
            'payment_link_id' => $v['payment_link_id'],
            'due_date' => $v['due_date'],
            'amount' => $v['amount'],
            'status' => 'active'
        ]);
        return response()->json(['message'=>'Installment created','installment'=>$inst], 201);
    }

    public function show($id)
    {
        $inst = Installment::findOrFail($id);
        return response()->json($inst, 200);
    }

    public function update(Request $request, $id)
    {
        $inst = Installment::findOrFail($id);
        $inst->update($request->only(['due_date','amount','status']));
        return response()->json(['message'=>'Installment updated','installment'=>$inst], 200);
    }

    public function destroy($id)
    {
        $inst = Installment::findOrFail($id);
        $inst->delete();
        return response()->json(['message'=>'Installment deleted'], 200);
    }
}
