<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;

class ProductImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected int $companyId;
    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected int $skippedCount = 0;
    protected array $importErrors = [];
    protected int $currentRow = 1; // 1 = heading row

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public function collection(Collection $rows)
    {
        Log::info('Import produits: chunk reçu', ['nb_rows' => $rows->count()]);

        foreach ($rows as $index => $row) {
            $this->currentRow++;
            $rowNum = $this->currentRow;

            // Normaliser les clés (supprimer accents, espaces, etc.)
            $row = $this->normalizeRow($row);

            Log::info("Import ligne {$rowNum}", ['row_data' => $row->toArray()]);

            // Vérifier les champs obligatoires - chercher le nom dans plusieurs colonnes possibles
            $nom = trim($row['nom'] ?? $row['name'] ?? $row['article'] ?? $row['produit'] ?? $row['designation'] ?? '');
            if (empty($nom)) {
                $this->importErrors[] = "Ligne {$rowNum}: Nom du produit manquant (colonne 'nom' vide ou introuvable).";
                $this->skippedCount++;
                continue;
            }

            $prixVente = $this->parseDecimal($row['prix_vente'] ?? $row['prix'] ?? $row['pv'] ?? $row['price'] ?? $row['prix_de_vente'] ?? 0);
            if ($prixVente <= 0) {
                $this->importErrors[] = "Ligne {$rowNum} ({$nom}): Prix de vente manquant ou invalide.";
                $this->skippedCount++;
                continue;
            }

            try {
                // Chercher fournisseur si spécifié
                $supplierId = null;
                $fournisseurName = trim($row['fournisseur'] ?? $row['supplier'] ?? '');
                if (!empty($fournisseurName)) {
                    $supplier = Supplier::where('company_id', $this->companyId)
                        ->where('name', $fournisseurName)
                        ->first();
                    $supplierId = $supplier?->id;
                }

                // Chercher si produit existe déjà
                $existingProduct = null;
                $codeBarre = trim($row['code_barre'] ?? $row['codebarre'] ?? $row['barcode'] ?? $row['ean'] ?? $row['code_barres'] ?? '');
                if (!empty($codeBarre)) {
                    $existingProduct = Product::where('company_id', $this->companyId)
                        ->where('barcode', $codeBarre)
                        ->first();
                }
                
                if (!$existingProduct) {
                    $existingProduct = Product::where('company_id', $this->companyId)
                        ->where('name', $nom)
                        ->first();
                }

                // Lire les valeurs avec flexibilité sur les noms de colonnes
                $prixAchat = $this->parseDecimal($row['prix_achat'] ?? $row['pa'] ?? $row['cout'] ?? $row['prix_dachat'] ?? $row['cost'] ?? 0);
                $stock = $this->parseInt($row['stock'] ?? $row['quantite'] ?? $row['qty'] ?? $row['qte'] ?? 0);
                $stockMin = $this->parseInt($row['stock_min'] ?? $row['stock_minimum'] ?? $row['seuil'] ?? 0);
                $unite = trim($row['unite'] ?? $row['unit'] ?? $row['uom'] ?? 'pièce');
                $description = trim($row['description'] ?? $row['desc'] ?? '');
                $prixGros = $this->parseDecimal($row['prix_gros'] ?? $row['wholesale'] ?? 0);
                $qteMinGros = $this->parseInt($row['qte_min_gros'] ?? $row['min_gros'] ?? 0);
                
                // TVA
                $tvaSale = $this->parseDecimal($row['tva_vente'] ?? $row['tva'] ?? $row['tax'] ?? '18');
                $tvaPurchase = $this->parseDecimal($row['tva_achat'] ?? $row['tva'] ?? $row['tax'] ?? '18');
                
                // Mode prix TTC/HT
                $prixTtcFlag = trim($row['prix_ttc'] ?? $row['ttc'] ?? 'oui');
                $priceIncludesVat = $this->parseBoolean($prixTtcFlag);

                // Calculer les prix HT/TTC
                if ($priceIncludesVat) {
                    $purchasePriceHt = $tvaPurchase > 0 ? $prixAchat / (1 + $tvaPurchase / 100) : $prixAchat;
                    $salePriceHt = $tvaSale > 0 ? $prixVente / (1 + $tvaSale / 100) : $prixVente;
                    $wholesalePriceHt = $tvaSale > 0 ? $prixGros / (1 + $tvaSale / 100) : $prixGros;
                } else {
                    $purchasePriceHt = $prixAchat;
                    $salePriceHt = $prixVente;
                    $wholesalePriceHt = $prixGros;
                    $prixAchat = $prixAchat * (1 + $tvaPurchase / 100);
                    $prixVente = $prixVente * (1 + $tvaSale / 100);
                    $prixGros = $prixGros * (1 + $tvaSale / 100);
                }

                $data = [
                    'company_id' => $this->companyId,
                    'name' => $nom,
                    'barcode' => !empty($codeBarre) ? $codeBarre : null,
                    'description' => !empty($description) ? $description : null,
                    'purchase_price' => round($prixAchat, 2),
                    'purchase_price_ht' => round($purchasePriceHt, 2),
                    'vat_rate_purchase' => $tvaPurchase,
                    'price' => round($prixVente, 2),
                    'sale_price_ht' => round($salePriceHt, 2),
                    'vat_rate_sale' => $tvaSale,
                    'prices_include_vat' => $priceIncludesVat,
                    'wholesale_price' => $prixGros > 0 ? round($prixGros, 2) : null,
                    'wholesale_price_ht' => $wholesalePriceHt > 0 ? round($wholesalePriceHt, 2) : null,
                    'min_wholesale_qty' => $qteMinGros,
                    'stock' => $stock,
                    'min_stock' => $stockMin,
                    'unit' => $unite ?: 'pièce',
                    'supplier_id' => $supplierId,
                ];

                if ($existingProduct) {
                    unset($data['stock']);
                    $existingProduct->update($data);
                    $this->updatedCount++;
                    Log::info("Produit mis à jour: {$nom}");
                } else {
                    Product::create($data);
                    $this->importedCount++;
                    Log::info("Produit créé: {$nom}");
                }

            } catch (\Exception $e) {
                $this->importErrors[] = "Ligne {$rowNum} ({$nom}): " . $e->getMessage();
                $this->skippedCount++;
                Log::error("Import produit erreur ligne {$rowNum}", [
                    'nom' => $nom,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Normalise les clés de la ligne : minuscules, sans accents, underscores
     */
    protected function normalizeRow(Collection $row): Collection
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeKey((string)$key);
            $normalized[$normalizedKey] = $value;
            // Garder aussi la clé originale
            if ($normalizedKey !== $key) {
                $normalized[$key] = $value;
            }
        }
        return collect($normalized);
    }

    protected function normalizeKey(string $key): string
    {
        $key = mb_strtolower($key);
        $key = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'ù', 'û', 'ü', 'ô', 'ö', 'î', 'ï', 'ç'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'u', 'u', 'u', 'o', 'o', 'i', 'i', 'c'],
            $key
        );
        $key = preg_replace('/[\s\-\.]+/', '_', $key);
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
        return trim($key, '_');
    }

    public function chunkSize(): int
    {
        return 200;
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

    public function getImportErrors(): array
    {
        return $this->importErrors;
    }

    protected function parseDecimal($value): float
    {
        if ($value === null || $value === '') return 0;
        $value = str_replace([' ', ','], ['', '.'], (string)$value);
        $value = preg_replace('/[^0-9.]/', '', $value);
        return (float) $value;
    }

    protected function parseInt($value): int
    {
        if ($value === null || $value === '') return 0;
        return (int) round($this->parseDecimal($value));
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        $value = mb_strtolower(trim((string)$value));
        return in_array($value, ['1', 'true', 'oui', 'yes', 'ttc', 'o']);
    }
}
