<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // e-MCeF (Mécanisme de Certification électronique des Factures - Bénin)
            $table->string('emcef_uid')->nullable()->after('ppf_synced_at');
            $table->string('emcef_nim')->nullable()->after('emcef_uid');
            $table->string('emcef_code_mecef')->nullable()->after('emcef_nim');
            $table->text('emcef_qr_code')->nullable()->after('emcef_code_mecef');
            $table->string('emcef_counters')->nullable()->after('emcef_qr_code');
            $table->string('emcef_status')->default('pending')->after('emcef_counters');
            $table->timestamp('emcef_certified_at')->nullable()->after('emcef_status');
            $table->text('emcef_error')->nullable()->after('emcef_certified_at');
            
            // Index pour les recherches
            $table->index('emcef_uid');
            $table->index('emcef_nim');
            $table->index('emcef_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['emcef_uid']);
            $table->dropIndex(['emcef_nim']);
            $table->dropIndex(['emcef_status']);
            
            $table->dropColumn([
                'emcef_uid',
                'emcef_nim',
                'emcef_code_mecef',
                'emcef_qr_code',
                'emcef_counters',
                'emcef_status',
                'emcef_certified_at',
                'emcef_error',
            ]);
        });
    }
};
