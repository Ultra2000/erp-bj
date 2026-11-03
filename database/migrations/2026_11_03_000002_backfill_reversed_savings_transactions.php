<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rattrapage : marque comme annulés (reversed_at) les mouvements déjà
     * contre-passés avant l'introduction de la colonne (référence ANNUL-<id>),
     * afin qu'ils soient exclus des totaux de collecte.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('savings_transactions', 'reversed_at')) {
            return;
        }

        DB::table('savings_transactions')
            ->where('type', 'adjustment')
            ->where('reference', 'like', 'ANNUL-%')
            ->orderBy('id')
            ->get(['reference', 'created_at'])
            ->each(function ($adj) {
                $origId = (int) preg_replace('/\D/', '', (string) $adj->reference);

                if ($origId > 0) {
                    DB::table('savings_transactions')
                        ->where('id', $origId)
                        ->whereNull('reversed_at')
                        ->update(['reversed_at' => $adj->created_at]);
                }
            });
    }

    public function down(): void
    {
        // Pas de rollback : on ne ré-active pas des mouvements annulés.
    }
};
