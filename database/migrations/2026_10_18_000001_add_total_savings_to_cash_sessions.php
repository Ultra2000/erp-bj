<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_sessions', 'total_savings')) {
                // Mouvements d'épargne en espèces rattachés à la session
                // (dépôts positifs, retraits négatifs) : affectent le tiroir.
                $table->decimal('total_savings', 15, 2)->default(0)->after('total_other');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('cash_sessions', 'total_savings')) {
                $table->dropColumn('total_savings');
            }
        });
    }
};
