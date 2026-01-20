<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // e-MCeF settings
            $table->string('emcef_nim')->nullable()->after('settings');
            $table->text('emcef_token')->nullable()->after('emcef_nim');
            $table->timestamp('emcef_token_expires_at')->nullable()->after('emcef_token');
            $table->boolean('emcef_enabled')->default(false)->after('emcef_token_expires_at');
            $table->boolean('emcef_sandbox')->default(true)->after('emcef_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'emcef_nim',
                'emcef_token',
                'emcef_token_expires_at',
                'emcef_enabled',
                'emcef_sandbox',
            ]);
        });
    }
};
