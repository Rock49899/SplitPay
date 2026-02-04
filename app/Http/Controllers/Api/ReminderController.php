<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Reminder;

class ReminderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        return response()->json(Reminder::orderBy('scheduled_at')->paginate(15), 200);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'target_type' => 'required|string',
            'target_id' => 'required|string',
            'scheduled_at' => 'required|date',
            'message' => 'required|string',
        ]);

        $reminder = Reminder::create(array_merge($v, ['id' => (string) Str::uuid()]));
        // In real app dispatch job to schedule notification
        return response()->json(['message'=>'Reminder scheduled','reminder'=>$reminder], 201);
    }

    public function destroy($id)
    {
        $r = Reminder::findOrFail($id);
        $r->delete();
        return response()->json(['message'=>'Reminder deleted'], 200);
    }
}
