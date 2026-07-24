<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vérification des stocks bas tous les jours à 8h
Schedule::command('stock:check-low --notify-email')
    ->dailyAt('08:00')
    ->description('Vérification quotidienne des stocks bas')
    ->emailOutputOnFailure(config('mail.from.address'));

// Sauvegarde quotidienne de la base de données (rétention 14 jours) + envoi par email
Schedule::command('backup:database --keep=14 --email=')
    ->dailyAt('02:00')
    ->description('Sauvegarde quotidienne de la base de données')
    ->emailOutputOnFailure(config('mail.from.address'));
