<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventories')) {
            return;
        }

        // Drop the global unique on reference and replace with per-company unique
        $indexes = DB::select("SHOW INDEX FROM inventories WHERE Column_name = 'reference' AND Non_unique = 0 AND Key_name != 'PRIMARY'");

        foreach ($indexes as $index) {
            DB::statement("ALTER TABLE inventories DROP INDEX `{$index->Key_name}`");
        }

        Schema::table('inventories', function (Blueprint $table) {
            $table->unique(['company_id', 'reference'], 'inventories_company_reference_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('inventories')) {
            return;
        }

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique('inventories_company_reference_unique');
            $table->unique('reference');
        });
    }
};
