<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter tax_specific_total à sale_items pour stocker la taxe spécifique séparément de la TVA
        if (!Schema::hasColumn('sale_items', 'tax_specific_total')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('tax_specific_total', 10, 2)->default(0)->after('tax_specific_amount')
                    ->comment('Total taxe spécifique pour cette ligne (amount × qty)');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('tax_specific_total');
        });
    }
};
