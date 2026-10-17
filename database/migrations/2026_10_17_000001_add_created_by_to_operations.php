<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracer l'opérateur (utilisateur) qui a créé l'opération.
     * sales / purchases / purchase_payments ne l'enregistraient pas encore.
     */
    public function up(): void
    {
        foreach (['sales', 'purchases', 'purchase_payments'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'created_by')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('created_by')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['sales', 'purchases', 'purchase_payments'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'created_by')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('created_by');
                });
            }
        }
    }
};
