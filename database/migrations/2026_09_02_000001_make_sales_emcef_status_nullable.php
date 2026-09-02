<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rendre sales.emcef_status nullable : un avoir (ou une vente d'une
     * entreprise sans e-MCeF) n'est pas dans le circuit de certification et
     * doit pouvoir avoir un statut null. La colonne était NOT NULL (défaut
     * 'pending'), ce qui faisait échouer la création dans ces cas.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('emcef_status')->nullable()->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('emcef_status')->default('pending')->nullable(false)->change();
        });
    }
};
