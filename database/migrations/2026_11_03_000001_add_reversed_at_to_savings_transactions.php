<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('savings_transactions', 'reversed_at')) {
                // Mouvement annulé / corrigé : exclu des totaux de collecte.
                $table->timestamp('reversed_at')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('savings_transactions', 'reversed_at')) {
                $table->dropColumn('reversed_at');
            }
        });
    }
};
