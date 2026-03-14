<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public User $user;

    public function __construct(string $otp, User $user)
    {
        $this->otp = $otp;
        $this->user = $user;
    }

    public function build()
    {
        $displayName = htmlspecialchars($this->user->name ?? $this->user->email);

        $body = "<p>Bonjour {$displayName},</p>"
            ."<p>Votre code de connexion est : <strong>{$this->otp}</strong>.</p>"
            ."<p>Ce code expire dans 10 minutes.</p>"
            ."<p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>";

        return $this->subject('Votre code OTP de connexion')->html($body);
    }
}
