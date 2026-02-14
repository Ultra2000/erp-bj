<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: utiliser Schema builder
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('quantity', 10, 3)->default(1)->change();
            });
        } else {
            // MySQL: syntaxe native
            DB::statement('ALTER TABLE sale_items MODIFY quantity DECIMAL(10,3) NOT NULL DEFAULT 1');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->integer('quantity')->default(1)->change();
            });
        } else {
            DB::statement('ALTER TABLE sale_items MODIFY quantity INT NOT NULL DEFAULT 1');
        }
    }
};
