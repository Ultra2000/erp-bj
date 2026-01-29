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
        Schema::table('sale_items', function (Blueprint $table) {
            // Indicateur prix de gros appliqué
            $table->boolean('is_wholesale')->default(false)->after('vat_category');
            // Prix de détail original (pour afficher l'économie)
            $table->decimal('retail_unit_price', 15, 2)->nullable()->after('is_wholesale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_wholesale', 'retail_unit_price']);
        });
    }
};
