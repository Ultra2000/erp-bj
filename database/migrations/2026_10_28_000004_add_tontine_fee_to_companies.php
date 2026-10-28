<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'tontine_fee')) {
                // Frais de tenue de compte unique (prélevés au 1er dépôt), en FCFA.
                $table->unsignedInteger('tontine_fee')->default(2000)->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'tontine_fee')) {
                $table->dropColumn('tontine_fee');
            }
        });
    }
};
