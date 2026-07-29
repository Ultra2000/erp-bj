<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // warehouses.code : unique global -> unique par entreprise
        // (permet à chaque entreprise d'avoir son entrepôt « MAIN »)
        Schema::table('warehouses', function (Blueprint $table) {
            try {
                $table->dropUnique(['code']);
            } catch (\Throwable $e) {
                // La contrainte n'existe peut-être plus / porte un autre nom
            }
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->unique(['company_id', 'code'], 'warehouses_company_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            try {
                $table->dropUnique('warehouses_company_code_unique');
            } catch (\Throwable $e) {
            }
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->unique('code');
        });
    }
};
