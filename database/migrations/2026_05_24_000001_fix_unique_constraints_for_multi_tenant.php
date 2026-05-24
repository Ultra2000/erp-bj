<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // invoice_number doit être unique PAR entreprise, pas globalement
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->unique(['company_id', 'invoice_number']);
        });

        // email client doit être unique PAR entreprise, pas globalement
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['company_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'invoice_number']);
            $table->unique('invoice_number');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'email']);
            $table->unique('email');
        });
    }
};
