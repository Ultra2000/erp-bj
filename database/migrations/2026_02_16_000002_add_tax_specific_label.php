<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Libellé personnalisable pour la taxe spécifique (ex: "Taxe de séjour", "Droit d'accise")
        if (!Schema::hasColumn('products', 'tax_specific_label')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('tax_specific_label')->nullable()->after('tax_specific_amount')
                    ->comment('Libellé personnalisé de la taxe spécifique (ex: Taxe de séjour)');
            });
        }

        if (!Schema::hasColumn('sale_items', 'tax_specific_label')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->string('tax_specific_label')->nullable()->after('tax_specific_total')
                    ->comment('Libellé de la taxe spécifique au moment de la vente');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tax_specific_label');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('tax_specific_label');
        });
    }
};
