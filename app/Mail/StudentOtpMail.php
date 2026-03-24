<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;

class StudentOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $student;

    public function __construct(string $otp, Student $student)
    {
        $this->otp = $otp;
        $this->student = $student;
    }

    public function build()
    {
        $this->student->loadMissing(['annexe.institution']);
        $institution = $this->student->annexe?->institution;
        $logoRelativePath = $institution?->logo ? ltrim($institution->logo, '/') : null;
        $logoDiskPath = $logoRelativePath ? storage_path('app/public/' . $logoRelativePath) : null;
        $logoExists = $logoDiskPath && file_exists($logoDiskPath);

        return $this->subject('Votre code OTP')
            ->view('emails.otp')
            ->with([
                'recipientName' => $this->student->first_name ?? $this->student->matricule,
                'otp' => $this->otp,
                'institutionName' => $institution?->name ?? '',
                'institutionLogo' => ($institution && $institution->logo)
                    ? asset('storage/' . $institution->logo)
                    : null,
                'institutionLogoPath' => $logoExists ? $logoDiskPath : null,
                'audienceLabel' => 'Espace Étudiant',
            ]);
    }
}
