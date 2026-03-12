<?php

namespace App\Mail;

use App\Models\Installment;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OverduePaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Créer une nouvelle instance de message.
     */
    public function __construct(
        public Student $student,
        public Installment $installment,
        public int $daysOverdue,
    ) {}

    /**
     * Récupérer l'enveloppe du message.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 RETARD DE PAIEMENT - {$this->student->full_name}",
        );
    }

    /**
     * Récupérer la définition du contenu du message.
     */
    public function content(): Content
    {
        $remaining = $this->installment->amount - $this->installment->amount_paid;
        $annexe = $this->student->annexe;

        return new Content(
            view: 'emails.overdue-payment',
            with: [
                'studentName' => $this->student->full_name,
                'matricule' => $this->student->matricule,
                'amount' => number_format($remaining, 0, ',', ' ') . ' FCFA',
                'dueDate' => $this->installment->due_date->format('d/m/Y'),
                'daysOverdue' => $this->daysOverdue,
                'annexeName' => $annexe->name,
                'annexePhone' => $annexe->phone ?? '',
                'annexeEmail' => $annexe->email ?? '',
            ],
        );
    }
}
