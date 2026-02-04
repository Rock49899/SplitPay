<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // Send a test notification (email/log)
    public function sendTest(Request $request)
    {
        $v = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        // Using MAIL_MAILER=log in .env will write to storage/logs
        Mail::raw($v['body'], function($m) use ($v) {
            $m->to($v['to'])->subject($v['subject']);
        });

        return response()->json(['message'=>'Notification sent (stub)'], 200);
    }
}
