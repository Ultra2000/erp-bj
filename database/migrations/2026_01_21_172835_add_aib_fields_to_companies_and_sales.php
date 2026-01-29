<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * AIB = Acompte sur Impôt assis sur les Bénéfices (Bénin)
     */
    public function up(): void
    {
        // Ajouter les paramètres AIB à la table companies
        Schema::table('companies', function (Blueprint $table) {
            $table->string('aib_mode')->default('auto')->after('emcef_sandbox'); // auto, manual, disabled
            $table->boolean('aib_exempt_retail')->default(true)->after('aib_mode'); // Exonérer ventes au détail
        });

        // Ajouter les champs AIB à la table sales
        Schema::table('sales', function (Blueprint $table) {
            $table->string('aib_rate')->nullable()->after('total_vat'); // A (1%), B (5%), null (exonéré)
            $table->decimal('aib_amount', 15, 2)->default(0)->after('aib_rate');
            $table->boolean('aib_exempt')->default(false)->after('aib_amount'); // Exonération manuelle
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['aib_mode', 'aib_exempt_retail']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['aib_rate', 'aib_amount', 'aib_exempt']);
        });
    }
};
