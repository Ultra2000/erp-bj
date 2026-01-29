<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Warehouse;
use App\Models\Sale;
use App\Models\Product;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'vat_rate',
        'unit_price_ht',
        'vat_amount',
        'total_price_ht',
        'total_price',
        'vat_category',
        'is_wholesale',
        'retail_unit_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'unit_price_ht' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_price_ht' => 'decimal:2',
        'total_price' => 'decimal:2',
        'retail_unit_price' => 'decimal:2',
        'is_wholesale' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Calculer les montants TVA
            $item->calculateVat();
        });

        static::saved(function ($item) {
            // Recharger la relation sale pour s'assurer qu'elle existe
            $sale = $item->sale ?? Sale::find($item->sale_id);
            if ($sale) {
                $sale->calculateTotal();
            }
        });

        static::created(function ($item) {
            // Recharger la relation sale
            $sale = $item->sale ?? Sale::find($item->sale_id);
            if ($sale && $sale->status === 'completed') {
                $warehouse = $sale->warehouse ?? Warehouse::getDefault($sale->company_id);
                if ($warehouse) {
                    if ($sale->type === 'credit_note') {
                        // Avoir : on réintègre le stock (au stock général)
                        $warehouse->addStockBack(
                            $item->product_id,
                            $item->quantity,
                            'credit_note',
                            "Avoir " . $sale->invoice_number
                        );
                    } else {
                        // Vente : déstockage intelligent FIFO depuis les emplacements
                        $warehouse->deductStockFIFO(
                            $item->product_id,
                            $item->quantity,
                            'sale',
                            "Vente " . $sale->invoice_number
                        );
                    }
                    
                    // Invalider le cache du stock produit pour mise à jour immédiate
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->clearStockCache();
                    }
                }
            }
        });

        static::updated(function ($item) {
            $sale = $item->sale ?? Sale::find($item->sale_id);
            if ($sale && $sale->status === 'completed') {
                $warehouse = $sale->warehouse ?? Warehouse::getDefault($sale->company_id);
                if ($warehouse) {
                    $oldQuantity = $item->getOriginal('quantity');
                    $diff = $item->quantity - $oldQuantity;
                    
                    if ($diff != 0) {
                        if ($sale->type === 'credit_note') {
                            // Avoir modifié : ajuster le stock réintégré
                            $warehouse->adjustStock(
                                $item->product_id,
                                $diff,
                                'credit_note_adjustment',
                                "Modification avoir " . $sale->invoice_number
                            );
                        } else {
                            // Vente modifiée
                            if ($diff > 0) {
                                // Quantité augmentée : déduire plus (FIFO)
                                $warehouse->deductStockFIFO(
                                    $item->product_id,
                                    $diff,
                                    'sale_adjustment',
                                    "Modification vente " . $sale->invoice_number
                                );
                            } else {
                                // Quantité diminuée : réintégrer la différence
                                $warehouse->addStockBack(
                                    $item->product_id,
                                    abs($diff),
                                    'sale_adjustment',
                                    "Modification vente " . $sale->invoice_number
                                );
                            }
                        }
                        
                        // Invalider le cache du stock produit
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->clearStockCache();
                        }
                    }
                }
            }
        });

        static::deleted(function ($item) {
            $sale = $item->sale ?? Sale::find($item->sale_id);
            if ($sale) {
                $sale->calculateTotal();

                if ($sale->status === 'completed') {
                    $warehouse = $sale->warehouse ?? Warehouse::getDefault($sale->company_id);
                    if ($warehouse) {
                        if ($sale->type === 'credit_note') {
                            // Suppression d'un article d'avoir : on retire le stock réintégré
                            $warehouse->deductStockFIFO(
                                $item->product_id,
                                $item->quantity,
                                'credit_note_cancel',
                                "Suppression article avoir " . $sale->invoice_number
                            );
                        } else {
                            // Suppression d'un article de vente : on réintègre le stock
                            $warehouse->addStockBack(
                                $item->product_id,
                                $item->quantity,
                                'sale_return',
                                "Suppression article vente " . $sale->invoice_number
                            );
                        }
                        
                        // Invalider le cache du stock produit
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->clearStockCache();
                        }
                    }
                }
            }
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calcule les montants HT, TVA et TTC
     */
    public function calculateVat(): void
    {
        // Le prix unitaire est considéré comme HT (c'est le prix de vente HT du produit)
        $this->unit_price_ht = $this->unit_price;
        $this->total_price_ht = $this->quantity * $this->unit_price_ht;
        
        // Vérifier si l'entreprise est en franchise de TVA
        $companyId = $this->sale?->company_id;
        $isVatFranchise = $companyId ? AccountingSetting::isVatFranchise($companyId) : false;
        
        if ($isVatFranchise) {
            // Franchise TVA : TVA = 0
            $this->vat_rate = 0;
            $this->vat_amount = 0;
            $this->total_price = $this->total_price_ht;
        } else {
            // Régime normal : calculer la TVA
            // Priorité: 1) taux défini sur l'item, 2) taux du produit, 3) défaut selon e-MCeF
            $vatRate = $this->vat_rate;
            
            if ($vatRate === null || $vatRate === '') {
                // Récupérer le taux du produit si disponible
                $product = $this->product ?? ($this->product_id ? Product::find($this->product_id) : null);
                $vatRate = $product?->vat_rate_sale;
            }
            
            if ($vatRate === null || $vatRate === '') {
                // Défaut: 18% (taux standard TVA Bénin)
                $vatRate = 18;
            }
            
            $this->vat_rate = $vatRate;
            $this->vat_amount = round($this->total_price_ht * ($vatRate / 100), 2);
            $this->total_price = $this->total_price_ht + $this->vat_amount;
        }
    }

    /**
     * Retourne le montant TTC unitaire
     */
    public function getUnitPriceTtcAttribute(): float
    {
        $vatRate = $this->vat_rate ?? 18;
        return round($this->unit_price_ht * (1 + $vatRate / 100), 2);
    }

    /**
     * Calcule l'économie réalisée grâce au prix de gros
     */
    public function getWholesaleSavingsAttribute(): float
    {
        if (!$this->is_wholesale || !$this->retail_unit_price) {
            return 0;
        }
        
        return round(($this->retail_unit_price - $this->unit_price) * $this->quantity, 2);
    }

    /**
     * Retourne le pourcentage de réduction appliqué
     */
    public function getWholesaleDiscountPercentAttribute(): float
    {
        if (!$this->is_wholesale || !$this->retail_unit_price || $this->retail_unit_price <= 0) {
            return 0;
        }
        
        $discount = $this->retail_unit_price - $this->unit_price;
        return round(($discount / $this->retail_unit_price) * 100, 2);
    }

    /**
     * Applique automatiquement le prix de gros si la quantité le permet
     * @param Product $product Le produit
     * @param int $quantity La quantité commandée
     * @return array ['unit_price' => float, 'is_wholesale' => bool, 'retail_price' => float|null]
     */
    public static function calculatePriceForQuantity(Product $product, int $quantity): array
    {
        $retailPrice = $product->sale_price_ht;
        
        if ($product->qualifiesForWholesale($quantity)) {
            return [
                'unit_price' => $product->wholesale_price_ht,
                'is_wholesale' => true,
                'retail_unit_price' => $retailPrice,
            ];
        }
        
        return [
            'unit_price' => $retailPrice,
            'is_wholesale' => false,
            'retail_unit_price' => null,
        ];
    }
}
