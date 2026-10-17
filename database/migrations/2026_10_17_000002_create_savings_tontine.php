<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tontine / épargne client (solde global « porte-monnaie ») :
     * - customers.savings_balance : solde courant (FCFA, entier)
     * - savings_transactions : journal des mouvements (dépôt, retrait,
     *   utilisation à l'achat, ajustement), avec solde après opération.
     */
    public function up(): void
    {
        if (Schema::hasTable('customers') && ! Schema::hasColumn('customers', 'savings_balance')) {
            Schema::table('customers', function (Blueprint $t) {
                $t->bigInteger('savings_balance')->default(0)->after('id');
            });
        }

        if (! Schema::hasTable('savings_transactions')) {
            Schema::create('savings_transactions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('company_id')->index();
                $t->unsignedBigInteger('customer_id')->index();
                // deposit | withdrawal | purchase | adjustment | refund
                $t->string('type');
                // Signé : + crédite le solde (dépôt/remboursement), - le débite (retrait/achat)
                $t->bigInteger('amount');
                $t->bigInteger('balance_after');
                $t->string('payment_method')->nullable();   // dépôt/retrait en espèces, etc.
                $t->unsignedBigInteger('sale_id')->nullable()->index();          // si utilisé pour un achat
                $t->unsignedBigInteger('cash_session_id')->nullable();
                $t->unsignedBigInteger('created_by')->nullable();                // opérateur
                $t->string('reference')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_transactions');
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'savings_balance')) {
            Schema::table('customers', function (Blueprint $t) {
                $t->dropColumn('savings_balance');
            });
        }
    }
};
