<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Utiliser SQL brut pour éviter les problèmes de compatibilité avec ->change()
        DB::statement('ALTER TABLE sale_items MODIFY quantity DECIMAL(10,3) NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sale_items MODIFY quantity INT NOT NULL DEFAULT 1');
    }
};
