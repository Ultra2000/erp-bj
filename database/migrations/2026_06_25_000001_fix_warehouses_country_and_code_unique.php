<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Élargir la colonne country de VARCHAR(2) à VARCHAR(100)
        //    et changer le défaut de 'FR' à 'BJ'
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('country', 100)->default('BJ')->change();
        });

        // Mettre à jour les valeurs existantes 'FR' → 'BJ'
        DB::table('warehouses')->where('country', 'FR')->update(['country' => 'BJ']);

        // 2. Corriger le unique global sur code → unique par company
        $indexes = collect(DB::select("SHOW INDEX FROM warehouses WHERE Column_name = 'code' AND Non_unique = 0"));
        foreach ($indexes as $index) {
            if ($index->Key_name !== 'warehouses_company_code_unique') {
                DB::statement("ALTER TABLE warehouses DROP INDEX `{$index->Key_name}`");
            }
        }

        $hasCompanyCodeIndex = collect(DB::select("SHOW INDEX FROM warehouses WHERE Key_name = 'warehouses_company_code_unique'"))->isNotEmpty();
        if (!$hasCompanyCodeIndex) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->unique(['company_id', 'code'], 'warehouses_company_code_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique('warehouses_company_code_unique');
            $table->unique('code');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('country', 2)->default('FR')->change();
        });
    }
};
