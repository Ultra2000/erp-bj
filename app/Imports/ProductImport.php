<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;

class ProductImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    use SkipsErrors, SkipsFailures;

    protected int $companyId;
    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected int $skippedCount = 0;
    protected array $errors = [];

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                // Chercher fournisseur si spécifié
                $supplierId = null;
                if (!empty($row['fournisseur'])) {
                    $supplier = Supplier::where('company_id', $this->companyId)
                        ->where(function ($q) use ($row) {
                            $q->where('name', $row['fournisseur'])
                              ->orWhere('code', $row['fournisseur']);
                        })
                        ->first();
                    $supplierId = $supplier?->id;
                }

                // Chercher si produit existe déjà (par code barre ou nom)
                $existingProduct = null;
                if (!empty($row['code_barre'])) {
                    $existingProduct = Product::where('company_id', $this->companyId)
                        ->where('barcode', $row['code_barre'])
                        ->first();
                }
                
                if (!$existingProduct && !empty($row['nom'])) {
                    $existingProduct = Product::where('company_id', $this->companyId)
                        ->where('name', $row['nom'])
                        ->first();
                }

                // Préparer les données
                $priceIncludesVat = $this->parseBoolean($row['prix_ttc'] ?? '1');
                $vatRateSale = $this->parseDecimal($row['tva_vente'] ?? '18');
                $vatRatePurchase = $this->parseDecimal($row['tva_achat'] ?? '18');

                // Calculer les prix HT/TTC selon le mode
                $purchasePrice = $this->parseDecimal($row['prix_achat'] ?? 0);
                $salePrice = $this->parseDecimal($row['prix_vente'] ?? 0);
                $wholesalePrice = $this->parseDecimal($row['prix_gros'] ?? 0);

                if ($priceIncludesVat) {
                    // Prix saisis en TTC
                    $purchasePriceHt = $vatRatePurchase > 0 ? $purchasePrice / (1 + $vatRatePurchase / 100) : $purchasePrice;
                    $salePriceHt = $vatRateSale > 0 ? $salePrice / (1 + $vatRateSale / 100) : $salePrice;
                    $wholesalePriceHt = $vatRateSale > 0 ? $wholesalePrice / (1 + $vatRateSale / 100) : $wholesalePrice;
                } else {
                    // Prix saisis en HT
                    $purchasePriceHt = $purchasePrice;
                    $salePriceHt = $salePrice;
                    $wholesalePriceHt = $wholesalePrice;
                    $purchasePrice = $purchasePrice * (1 + $vatRatePurchase / 100);
                    $salePrice = $salePrice * (1 + $vatRateSale / 100);
                    $wholesalePrice = $wholesalePrice * (1 + $vatRateSale / 100);
                }

                $data = [
                    'company_id' => $this->companyId,
                    'name' => trim($row['nom']),
                    'barcode' => !empty($row['code_barre']) ? trim($row['code_barre']) : null,
                    'description' => $row['description'] ?? null,
                    'purchase_price' => round($purchasePrice, 2),
                    'purchase_price_ht' => round($purchasePriceHt, 2),
                    'vat_rate_purchase' => $vatRatePurchase,
                    'price' => round($salePrice, 2),
                    'sale_price_ht' => round($salePriceHt, 2),
                    'vat_rate_sale' => $vatRateSale,
                    'prices_include_vat' => $priceIncludesVat,
                    'wholesale_price' => $wholesalePrice > 0 ? round($wholesalePrice, 2) : null,
                    'wholesale_price_ht' => $wholesalePriceHt > 0 ? round($wholesalePriceHt, 2) : null,
                    'min_wholesale_qty' => $this->parseInt($row['qte_min_gros'] ?? 0),
                    'stock' => $this->parseInt($row['stock'] ?? 0),
                    'min_stock' => $this->parseInt($row['stock_min'] ?? 0),
                    'unit' => $row['unite'] ?? 'pièce',
                    'supplier_id' => $supplierId,
                ];

                if ($existingProduct) {
                    // Mise à jour du produit existant (sans modifier le stock)
                    unset($data['stock']); // Ne pas écraser le stock existant via update
                    $existingProduct->update($data);
                    $this->updatedCount++;
                } else {
                    // Création d'un nouveau produit
                    Product::create($data);
                    $this->importedCount++;
                }

            } catch (\Exception $e) {
                $this->errors[] = "Ligne avec '{$row['nom']}': " . $e->getMessage();
                $this->skippedCount++;
                Log::error('Import produit erreur', [
                    'row' => $row->toArray(),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'prix_vente' => 'required|numeric|min:0',
            'prix_achat' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'stock_min' => 'nullable|integer|min:0',
            'code_barre' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'unite' => 'nullable|string|max:50',
            'tva_vente' => 'nullable|numeric|min:0|max:100',
            'tva_achat' => 'nullable|numeric|min:0|max:100',
            'prix_gros' => 'nullable|numeric|min:0',
            'qte_min_gros' => 'nullable|integer|min:0',
            'fournisseur' => 'nullable|string|max:255',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nom.required' => 'Le nom du produit est obligatoire.',
            'prix_vente.required' => 'Le prix de vente est obligatoire.',
            'prix_vente.numeric' => 'Le prix de vente doit être un nombre.',
            'prix_achat.numeric' => 'Le prix d\'achat doit être un nombre.',
            'stock.integer' => 'Le stock doit être un nombre entier.',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function parseDecimal($value): float
    {
        if (empty($value)) return 0;
        // Remplacer virgule par point et nettoyer
        $value = str_replace([' ', ','], ['', '.'], (string)$value);
        return (float) $value;
    }

    protected function parseInt($value): int
    {
        if (empty($value)) return 0;
        return (int) $value;
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        $value = strtolower(trim((string)$value));
        return in_array($value, ['1', 'true', 'oui', 'yes', 'ttc']);
    }
}
