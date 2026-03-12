<?php

namespace App\Console\Commands;

use App\Jobs\CheckDueDatesJob;
use Illuminate\Console\Command;

class CheckPaymentDueDates extends Command
{
    /**
     * Nom et signature de la commande console.
     *
     * @var string
     */
    protected $signature = 'payments:check-due-dates';

    /**
     * Description de la commande console.
     *
     * @var string
     */
    protected $description = 'Vérifie les échéances de paiement et crée des notifications';

    /**
     * Exécuter la commande console.
     */
    public function handle(): int
    {
        $this->info('Vérification des échéances de paiement...');

        try {
            CheckDueDatesJob::dispatch();
            
            $this->info('Job de vérification des échéances lancé avec succès.');
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erreur lors du lancement du job : ' . $e->getMessage());
            
            return self::FAILURE;
        }
    }
}
