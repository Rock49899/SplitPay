<?php

namespace App\Jobs;

use App\Models\Installment;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDueDatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives.
     */
    public $tries = 2;

    /**
     * Timeout en secondes.
     */
    public $timeout = 120;

    /**
     * Exécuter le job.
     * Vérifie toutes les échéances et crée des notifications si nécessaire.
     */
    public function handle(): void
    {
        $this->checkApproachingDeadlines();
        $this->checkOverduePayments();
    }

    /**
     * Vérifier les échéances qui approchent (dans 3 jours).
     */
    protected function checkApproachingDeadlines(): void
    {
        $targetDate = now()->addDays(3)->toDateString();

        $installments = Installment::where('status', 'active')
            ->whereDate('due_date', $targetDate)
            ->whereRaw('amount_paid < amount')
            ->with(['paymentLink.student.annexe'])
            ->get();

        foreach ($installments as $installment) {
            try {
                Notification::dueDateApproaching($installment, 3);
                
                Log::info('Due date approaching notification created', [
                    'installment_id' => $installment->id,
                    'student_id' => $installment->paymentLink->student->id,
                    'due_date' => $installment->due_date->format('Y-m-d'),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create approaching deadline notification', [
                    'installment_id' => $installment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($installments->count() > 0) {
            Log::info('Check approaching deadlines completed', [
                'count' => $installments->count(),
                'target_date' => $targetDate,
            ]);
        }
    }

    /**
     * Vérifier les paiements en retard.
     */
    protected function checkOverduePayments(): void
    {
        $today = now()->toDateString();

        $overdueInstallments = Installment::where('status', 'active')
            ->whereDate('due_date', '<', $today)
            ->whereRaw('amount_paid < amount')
            ->with(['paymentLink.student.annexe'])
            ->get();

        $createdCount = 0;

        foreach ($overdueInstallments as $installment) {
            try {
                // Vérifier si une notification de retard n'a pas déjà été créée récemment (dernières 24h)
                $existingNotification = Notification::where('type', 'payment_overdue')
                    ->where('message', 'like', '%' . $installment->paymentLink->student->full_name . '%')
                    ->where('created_at', '>=', now()->subDay())
                    ->exists();

                if (!$existingNotification) {
                    Notification::overduePayment($installment);
                    $createdCount++;
                    
                    Log::info('Overdue payment notification created', [
                        'installment_id' => $installment->id,
                        'student_id' => $installment->paymentLink->student->id,
                        'due_date' => $installment->due_date->format('Y-m-d'),
                        'days_overdue' => now()->diffInDays($installment->due_date),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create overdue payment notification', [
                    'installment_id' => $installment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($overdueInstallments->count() > 0) {
            Log::info('Check overdue payments completed', [
                'total_overdue' => $overdueInstallments->count(),
                'notifications_created' => $createdCount,
            ]);
        }
    }

    /**
     * Gérer l'échec du job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckDueDatesJob failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
