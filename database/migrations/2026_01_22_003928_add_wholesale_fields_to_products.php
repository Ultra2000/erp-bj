<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute les champs pour la gestion des prix de gros
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Prix de gros (HT ou TTC selon prices_include_vat)
            $table->decimal('wholesale_price', 15, 2)->nullable()->after('sale_price_ht');
            // Prix de gros HT calculé
            $table->decimal('wholesale_price_ht', 15, 2)->nullable()->after('wholesale_price');
            // Quantité minimale pour bénéficier du prix de gros
            $table->unsignedInteger('min_wholesale_qty')->default(10)->after('wholesale_price_ht');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['wholesale_price', 'wholesale_price_ht', 'min_wholesale_qty']);
        });
    }
};
