<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Warehouse;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'unit_price',
        'vat_rate',
        'unit_price_ht',
        'vat_amount',
        'total_price_ht',
        'total_price',
    ];

    protected $casts = [
        // Montants en FCFA : entiers
        'unit_price' => 'integer',
        'unit_price_ht' => 'integer',
        'vat_amount' => 'integer',
        'total_price_ht' => 'integer',
        'total_price' => 'integer',
        // Taux de TVA : décimales possibles
        'vat_rate' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($item) {
            $item->calculateVat();
        });

        static::updating(function ($item) {
            $item->calculateVat();
        });

        // Mise à jour du stock uniquement si l'achat est terminé
        static::created(function ($item) {
            if ($item->purchase->status === 'completed') {
                $warehouse = $item->purchase->warehouse ?? Warehouse::getDefault($item->purchase->company_id);
                if ($warehouse) {
                    $warehouse->adjustStock(
                        $item->product_id,
                        $item->quantity,
                        'purchase',
                        "Ajout article achat {$item->purchase->invoice_number}",
                        null
                    );
                }
            }
        });

        static::updated(function ($item) {
            if ($item->purchase->status === 'completed') {
                $warehouse = $item->purchase->warehouse ?? Warehouse::getDefault($item->purchase->company_id);
                if ($warehouse) {
                    $oldQuantity = $item->getOriginal('quantity');
                    $diff = $item->quantity - $oldQuantity;
                    
                    if ($diff != 0) {
                        $warehouse->adjustStock(
                            $item->product_id,
                            $diff,
                            'purchase_adjustment',
                            "Modification quantité achat {$item->purchase->invoice_number}",
                            null
                        );
                    }
                }
            }
        });

        static::deleted(function ($item) {
            if ($item->purchase->status === 'completed') {
                $warehouse = $item->purchase->warehouse ?? Warehouse::getDefault($item->purchase->company_id);
                if ($warehouse) {
                    $warehouse->adjustStock(
                        $item->product_id,
                        -$item->quantity,
                        'purchase_cancellation',
                        "Suppression article achat {$item->purchase->invoice_number}",
                        null
                    );
                }
            }
        });
    }

    /**
     * Calcule les montants HT, TVA et TTC
     */
    public function calculateVat(): void
    {
        // Le prix unitaire est considéré comme HT — montants entiers (FCFA)
        $this->unit_price_ht = round($this->unit_price);
        $this->total_price_ht = round($this->quantity * $this->unit_price_ht);

        // Calculer la TVA déductible
        $vatRate = $this->vat_rate ?? 20;
        $this->vat_amount = round($this->total_price_ht * ($vatRate / 100));

        // Total TTC
        $this->total_price = $this->total_price_ht + $this->vat_amount;
    }

    /**
     * Retourne le montant TTC unitaire
     */
    public function getUnitPriceTtcAttribute(): float
    {
        $vatRate = $this->vat_rate ?? 20;
        return round($this->unit_price_ht * (1 + $vatRate / 100), 2);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
} 