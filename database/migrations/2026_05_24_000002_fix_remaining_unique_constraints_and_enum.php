<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. products.code : unique global → unique par company ───
        Schema::table('products', function (Blueprint $table) {
            try {
                $table->dropUnique(['code']);
            } catch (\Exception $e) {
                // La contrainte n'existe peut-être plus
            }
        });
        Schema::table('products', function (Blueprint $table) {
            $table->unique(['company_id', 'code'], 'products_company_code_unique');
        });

        // ─── 2. purchases.invoice_number : unique global → unique par company ───
        Schema::table('purchases', function (Blueprint $table) {
            try {
                $table->dropUnique(['invoice_number']);
            } catch (\Exception $e) {
            }
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->unique(['company_id', 'invoice_number'], 'purchases_company_invoice_unique');
        });

        // ─── 3. stock_transfers.reference : unique global → unique par company ───
        Schema::table('stock_transfers', function (Blueprint $table) {
            try {
                $table->dropUnique(['reference']);
            } catch (\Exception $e) {
            }
        });
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->unique(['company_id', 'reference'], 'stock_transfers_company_reference_unique');
        });

        // ─── 4. inventories.reference : unique global → unique par company ───
        Schema::table('inventories', function (Blueprint $table) {
            try {
                $table->dropUnique(['reference']);
            } catch (\Exception $e) {
            }
        });
        Schema::table('inventories', function (Blueprint $table) {
            $table->unique(['company_id', 'reference'], 'inventories_company_reference_unique');
        });

        // ─── 5. quotes.quote_number : unique global → unique par company ───
        Schema::table('quotes', function (Blueprint $table) {
            try {
                $table->dropUnique(['quote_number']);
            } catch (\Exception $e) {
            }
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->unique(['company_id', 'quote_number'], 'quotes_company_number_unique');
        });

        // ─── 6. delivery_notes.delivery_number : unique global → unique par company ───
        Schema::table('delivery_notes', function (Blueprint $table) {
            try {
                $table->dropUnique(['delivery_number']);
            } catch (\Exception $e) {
            }
        });
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->unique(['company_id', 'delivery_number'], 'delivery_notes_company_number_unique');
        });

        // ─── 7. stock_movements.type : ENUM trop restrictif → STRING ───
        // Les observers utilisent des types comme 'sale_adjustment', 'credit_note',
        // 'credit_note_cancel', 'sale_return' qui ne sont pas dans l'ENUM original.
        // On convertit en VARCHAR pour plus de flexibilité.
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN `type` VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        // Restaurer les contraintes globales
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_company_code_unique');
            $table->unique('code');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('purchases_company_invoice_unique');
            $table->unique('invoice_number');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropUnique('stock_transfers_company_reference_unique');
            $table->unique('reference');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique('inventories_company_reference_unique');
            $table->unique('reference');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique('quotes_company_number_unique');
            $table->unique('quote_number');
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropUnique('delivery_notes_company_number_unique');
            $table->unique('delivery_number');
        });

        // Restaurer l'ENUM (avec les types originaux uniquement)
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN `type` ENUM('purchase','sale','transfer_out','transfer_in','adjustment_in','adjustment_out','inventory','return_in','return_out','production_in','production_out','waste','initial') NOT NULL");
    }
};
