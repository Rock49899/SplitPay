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
        $this->user->loadMissing(['annexe.institution', 'annexes.institution']);

        $institution = $this->user->annexe?->institution
            ?? $this->user->annexes->first()?->institution;

        $logoRelativePath = $institution?->logo ? ltrim($institution->logo, '/') : null;
        $logoDiskPath = $logoRelativePath ? storage_path('app/public/' . $logoRelativePath) : null;
        $logoExists = $logoDiskPath && file_exists($logoDiskPath);

        return $this->subject('Votre code OTP de connexion')
            ->view('emails.otp')
            ->with([
                'recipientName' => $this->user->name ?? $this->user->email,
                'otp' => $this->otp,
                'institutionName' => $institution?->name ?? '',
                'institutionLogo' => ($institution && $institution->logo)
                    ? asset('storage/' . $institution->logo)
                    : null,
                'institutionLogoPath' => $logoExists ? $logoDiskPath : null,
                'audienceLabel' => 'Espace Administration',
            ]);
    }
}
