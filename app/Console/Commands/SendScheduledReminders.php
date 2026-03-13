<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderJob;
use App\Models\Reminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledReminders extends Command
{
    /**
     * Nom et signature de la commande console.
     *
     * @var string
     */
    protected $signature = 'reminders:send
                            {--dry-run : Afficher les rappels qui seraient envoyés sans les envoyer}';

    /**
     * Description de la commande console.
     *
     * @var string
     */
    protected $description = 'Envoie les rappels automatiques de paiement selon les échéances';

    /**
     * Exécuter la commande console.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Recherche des rappels actifs...');

        // Récupérer tous les rappels actifs
        $reminders = Reminder::active()->orderedByDays()->get();

        if ($reminders->isEmpty()) {
            $this->warn('Aucun rappel actif configuré.');
            return self::SUCCESS;
        }

        $this->info("{$reminders->count()} rappel(s) actif(s) trouvé(s).\n");

        $totalSent = 0;

        foreach ($reminders as $reminder) {
            $this->line("Traitement du rappel : {$reminder->days_before} jour(s) avant échéance");

            // Récupérer les échéances concernées
            $installments = $reminder->getTargetInstallments();

            if ($installments->isEmpty()) {
                $this->comment("   → Aucune échéance à traiter pour ce rappel.");
                continue;
            }

            $this->info("   → {$installments->count()} étudiant(s) concerné(s)");

            if ($isDryRun) {
                // Mode dry-run : afficher sans envoyer
                foreach ($installments as $installment) {
                    $student = $installment->paymentLink->student;
                    $this->line("      • {$student->full_name} ({$student->matricule}) - {$installment->due_date->format('d/m/Y')}");
                }
            } else {
                // Envoyer les rappels
                foreach ($installments as $installment) {
                    try {
                        SendReminderJob::dispatch($reminder, $installment);
                        $totalSent++;
                        
                        $student = $installment->paymentLink->student;
                        $this->line("      ✓ {$student->full_name} ({$student->matricule})");
                    } catch (\Exception $e) {
                        $this->error("      ✗ Erreur : " . $e->getMessage());
                        Log::error('Failed to dispatch SendReminderJob', [
                            'reminder_id' => $reminder->id,
                            'installment_id' => $installment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $this->newLine();
        }

        if ($isDryRun) {
            $this->info('Mode dry-run : Aucun email n\'a été envoyé.');
        } else {
            $this->info("{$totalSent} rappel(s) envoyé(s) avec succès !");
            
            Log::info('Scheduled reminders sent', [
                'total_sent' => $totalSent,
                'total_reminders' => $reminders->count(),
            ]);
        }

        return self::SUCCESS;
    }
}
