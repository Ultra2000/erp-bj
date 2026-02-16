<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter tax_specific_amount à products
        if (!Schema::hasColumn('products', 'tax_specific_amount')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('tax_specific_amount', 10, 2)->nullable()->after('vat_category')
                    ->comment('Montant taxe spécifique par unité (Groupe E e-MCeF)');
            });
        }

        // Ajouter tax_specific_amount à sale_items
        if (!Schema::hasColumn('sale_items', 'tax_specific_amount')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('tax_specific_amount', 10, 2)->nullable()->after('vat_category')
                    ->comment('Montant taxe spécifique par unité (Groupe E e-MCeF)');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tax_specific_amount');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('tax_specific_amount');
        });
    }
};
