<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colonnes monétaires (FCFA, sans sous-unité) à passer en entier.
     */
    protected array $moneyColumns = [
        'products' => ['purchase_price', 'purchase_price_ht', 'price', 'sale_price_ht', 'wholesale_price', 'wholesale_price_ht', 'tax_specific_amount'],
        'sale_items' => ['unit_price', 'unit_price_ht', 'vat_amount', 'total_price_ht', 'total_price', 'tax_specific_amount', 'tax_specific_total', 'retail_unit_price'],
        'sales' => ['total', 'total_ht', 'total_vat', 'amount_paid', 'aib_amount'],
        'purchase_items' => ['unit_price', 'unit_price_ht', 'vat_amount', 'total_price_ht', 'total_price'],
        'purchases' => ['total', 'total_ht', 'total_vat', 'amount_paid'],
    ];

    public function up(): void
    {
        // Uniquement MySQL : SQLite (dev) s'appuie déjà sur les casts entiers du modèle.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        $this->convertAll('DECIMAL(15,0)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        $this->convertAll('DECIMAL(15,2)');
    }

    /**
     * Applique le nouveau type à chaque colonne en conservant NULL/NOT NULL et le défaut.
     */
    protected function convertAll(string $type): void
    {
        foreach ($this->moneyColumns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $meta = DB::selectOne(
                    'SELECT IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [$table, $column]
                );

                $nullable = ($meta && $meta->IS_NULLABLE === 'YES') ? 'NULL' : 'NOT NULL';

                // Attention : MySQL/MariaDB peut renvoyer la CHAÎNE 'NULL' pour un défaut NULL.
                $colDefault = $meta->COLUMN_DEFAULT ?? null;
                $default = '';
                if ($colDefault !== null && strtoupper((string) $colDefault) !== 'NULL') {
                    $default = is_numeric($colDefault)
                        ? 'DEFAULT ' . $colDefault
                        : "DEFAULT '" . $colDefault . "'";
                }

                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$type} {$nullable} {$default}");
            }
        }
    }
};
