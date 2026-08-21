<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suivi du retrait/livraison de la marchandise sur la vente :
     * - delivery_status : 'delivered' (retiré/emporté) | 'to_deliver' (à retirer)
     * - delivered_at    : date de remise au client
     *
     * Les ventes existantes sont considérées comme déjà retirées.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'delivery_status')) {
                $table->string('delivery_status')->default('delivered')->after('payment_status');
            }
            if (! Schema::hasColumn('sales', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('delivery_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'delivered_at')) {
                $table->dropColumn('delivered_at');
            }
            if (Schema::hasColumn('sales', 'delivery_status')) {
                $table->dropColumn('delivery_status');
            }
        });
    }
};
