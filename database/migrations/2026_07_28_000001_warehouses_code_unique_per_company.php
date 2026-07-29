<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * warehouses.code : unique global -> unique par entreprise.
     * Migration tolérante : ne échoue jamais si l'index est absent / nommé autrement.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Supprimer tout index UNIQUE portant uniquement sur `code`
            $indexes = DB::select(
                "SELECT INDEX_NAME, COUNT(*) AS col_count
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouses'
                   AND NON_UNIQUE = 0 AND INDEX_NAME <> 'PRIMARY'
                 GROUP BY INDEX_NAME
                 HAVING SUM(COLUMN_NAME = 'code') = 1 AND col_count = 1"
            );
            foreach ($indexes as $idx) {
                try {
                    DB::statement("ALTER TABLE `warehouses` DROP INDEX `{$idx->INDEX_NAME}`");
                } catch (\Throwable $e) {
                }
            }

            // Ajouter l'index composite s'il n'existe pas déjà
            $exists = DB::selectOne(
                "SELECT 1 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouses'
                   AND INDEX_NAME = 'warehouses_company_code_unique' LIMIT 1"
            );
            if (! $exists) {
                try {
                    DB::statement("ALTER TABLE `warehouses` ADD UNIQUE `warehouses_company_code_unique` (`company_id`, `code`)");
                } catch (\Throwable $e) {
                }
            }

            return;
        }

        // SQLite / autres : approche Schema tolérante
        try {
            Schema::table('warehouses', fn (Blueprint $t) => $t->dropUnique(['code']));
        } catch (\Throwable $e) {
        }
        try {
            Schema::table('warehouses', fn (Blueprint $t) => $t->unique(['company_id', 'code'], 'warehouses_company_code_unique'));
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        try {
            Schema::table('warehouses', fn (Blueprint $t) => $t->dropUnique('warehouses_company_code_unique'));
        } catch (\Throwable $e) {
        }
    }
};
