<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class DateHelper
{
    /**
     * Formate une date/heure dans le fuseau d'AFFICHAGE (config app.display_timezone),
     * les dates étant stockées en UTC. Retourne '' pour une valeur vide.
     *
     * @param  \Carbon\CarbonInterface|\DateTimeInterface|string|null  $value
     */
    public static function fmt($value, string $format = 'd/m/Y à H:i'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            $date = $value instanceof \DateTimeInterface
                ? Carbon::instance($value instanceof Carbon ? $value : Carbon::parse($value))
                : Carbon::parse($value);
        } catch (\Throwable $e) {
            return '';
        }

        return $date->copy()
            ->timezone(config('app.display_timezone', 'Africa/Porto-Novo'))
            ->format($format);
    }
}
