<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('expense_category_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();   // bénéficiaire (utilisateur)
                $table->string('beneficiary')->nullable();                     // bénéficiaire libre
                $table->unsignedBigInteger('amount')->default(0);              // FCFA
                $table->string('payment_method')->nullable();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->string('receipt_path')->nullable();
                $table->date('spent_at')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
