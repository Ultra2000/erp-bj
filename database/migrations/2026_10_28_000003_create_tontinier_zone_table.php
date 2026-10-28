<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tontinier_zone')) {
            Schema::create('tontinier_zone', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();   // le tontinier (utilisateur)
                $table->unsignedBigInteger('zone_id')->index();
                $table->timestamps();
                $table->unique(['user_id', 'zone_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tontinier_zone');
    }
};
