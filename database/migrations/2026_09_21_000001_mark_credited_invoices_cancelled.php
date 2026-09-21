<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Régularise l'historique : toute facture ayant déjà un avoir doit être
     * marquée 'cancelled' (annulée), pour disparaître des impayés (caisse) et
     * des créances. Concerne les avoirs générés avant l'ajout de ce statut.
     */
    public function up(): void
    {
        $parentIds = DB::table('sales')
            ->where('type', 'credit_note')
            ->whereNotNull('parent_id')
            ->distinct()
            ->pluck('parent_id');

        if ($parentIds->isEmpty()) {
            return;
        }

        DB::table('sales')
            ->whereIn('id', $parentIds)
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '<>', 'credit_note'))
            ->where(fn ($q) => $q->whereNull('payment_status')->orWhere('payment_status', '<>', 'cancelled'))
            ->update(['payment_status' => 'cancelled']);
    }

    public function down(): void
    {
        // Pas de rollback : on ne peut pas restaurer le statut de paiement d'origine.
    }
};
