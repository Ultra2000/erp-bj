<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Réaligne les sessions de caisse déjà clôturées : l'attendu et l'écart
     * ne doivent plus inclure l'épargne/tontine (gérée hors caisse).
     * Attendu = fond + espèces (ventes) ; écart = compté - attendu.
     */
    public function up(): void
    {
        if (! Schema::hasTable('cash_sessions')) {
            return;
        }

        DB::table('cash_sessions')
            ->whereNotNull('closed_at')
            ->orderBy('id')
            ->get(['id', 'opening_amount', 'total_cash', 'closing_amount'])
            ->each(function ($s) {
                $expected = (float) $s->opening_amount + (float) $s->total_cash;
                $difference = (float) $s->closing_amount - $expected;

                DB::table('cash_sessions')->where('id', $s->id)->update([
                    'expected_amount' => $expected,
                    'difference' => $difference,
                ]);
            });
    }

    public function down(): void
    {
        // Pas de rollback.
    }
};
