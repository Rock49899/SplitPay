<?php

namespace App\Mail;

use App\Models\Installment;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Créer une nouvelle instance de message.
     */
    public function __construct(
        public Student $student,
        public Installment $installment,
        public string $message,
        public int $daysBeforeDue,
    ) {}

    /**
     * Récupérer l'enveloppe du message.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->daysBeforeDue) {
            0 => '🚨 RAPPEL : Échéance de paiement AUJOURD\'HUI',
            1 => '⏰ RAPPEL : Échéance de paiement DEMAIN',
            default => "📅 Rappel : Échéance de paiement dans {$this->daysBeforeDue} jours",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Récupérer la définition du contenu du message.
     */
    public function content(): Content
    {
        $remaining = $this->installment->amount - $this->installment->amount_paid;
        $annexe = $this->student->annexe;
        $institution = $annexe->institution;

        return new Content(
            view: 'emails.payment-reminder',
            with: [
                'studentName' => $this->student->full_name,
                'matricule' => $this->student->matricule,
                'amount' => number_format($remaining, 0, ',', ' ') . ' FCFA',
                'dueDate' => $this->installment->due_date->format('d/m/Y'),
                'daysBeforeDue' => $this->daysBeforeDue,
                'customMessage' => $this->message,
                'annexeName' => $annexe->name,
                'annexePhone' => $annexe->phone ?? '',
                'annexeEmail' => $annexe->email ?? '',
                'institutionName' => $institution->name ?? '',
                'institutionLogo' => $institution->logo ? asset('storage/' . $institution->logo) : null,
            ],
        );
    }
}
