<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SCHEDULER - Tâches automatiques
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

// Envoyer les rappels automatiques chaque jour à 8h00 du matin
Schedule::command('reminders:send')
    ->dailyAt('08:00')
    ->timezone('Africa/Abidjan')
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Scheduled reminders sent successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Failed to send scheduled reminders');
    });

// Vérifier les échéances et créer des notifications 2 fois par jour (9h et 15h)
Schedule::command('payments:check-due-dates')
    ->twiceDaily(9, 15)
    ->timezone('Africa/Abidjan')
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Payment due dates checked successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Failed to check payment due dates');
    });

