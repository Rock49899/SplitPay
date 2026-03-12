<?php

namespace App\Jobs;

use App\Mail\PaymentReminderMail;
use App\Models\Installment;
use App\Models\Notification;
use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives avant d'abandonner.
     */
    public $tries = 3;

    /**
     * Timeout en secondes.
     */
    public $timeout = 60;

    /**
     * Créer une nouvelle instance de job.
     */
    public function __construct(
        public Reminder $reminder,
        public Installment $installment,
    ) {}

    /**
     * Exécuter le job.
     */
    public function handle(): void
    {
        try {
            $student = $this->installment->paymentLink->student;
            
            // Calculer le montant restant
            $remaining = $this->installment->amount - $this->installment->amount_paid;
            
            // Générer le lien de paiement
            $paymentUrl = url('/payment/' . $this->installment->paymentLink->token);
            
            // Remplacer les variables dans le message
            $message = $this->reminder->parseMessage([
                'student_name' => $student->full_name,
                'amount' => number_format($remaining, 0, ',', ' ') . ' FCFA',
                'due_date' => $this->installment->due_date->format('d/m/Y'),
                'payment_link' => $paymentUrl,
            ]);

            // Déterminer l'email du destinataire : parent ou étudiant
            $recipientEmail = $student->parent_email ?? $student->email;
            $recipientName = $student->parent_name ?? $student->full_name;

            if (!$recipientEmail) {
                Log::warning('No email found for student', [
                    'student_id' => $student->id,
                    'student_name' => $student->full_name,
                ]);
                return;
            }

            // Envoyer l'email
            Mail::to($recipientEmail, $recipientName)
                ->send(new PaymentReminderMail(
                    student: $student,
                    installment: $this->installment,
                    message: $message,
                    daysBeforeDue: $this->reminder->days_before,
                ));

            // Mettre à jour le tracking de l'installment
            $this->installment->update([
                'last_reminder_sent_at' => now(),
                'reminder_count' => $this->installment->reminder_count + 1,
            ]);

            Log::info('Payment reminder sent', [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'recipient_email' => $recipientEmail,
                'days_before' => $this->reminder->days_before,
                'due_date' => $this->installment->due_date->format('Y-m-d'),
                'reminder_count' => $this->installment->reminder_count + 1,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send payment reminder', [
                'reminder_id' => $this->reminder->id,
                'installment_id' => $this->installment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw pour permettre les retry
        }
    }

    /**
     * Gérer l'échec du job après toutes les tentatives.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendReminderJob failed after all retries', [
            'reminder_id' => $this->reminder->id,
            'installment_id' => $this->installment->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
