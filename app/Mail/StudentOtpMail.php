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
        $body = "<p>Bonjour ".htmlspecialchars($this->student->first_name ?? $this->student->matricule).",</p>"
              ."<p>Votre code de connexion est : <strong>{$this->otp}</strong>.</p>"
              ."<p>Il expire dans 10 minutes.</p>";

        return $this->subject('Votre code OTP')->html($body);
    }
}
