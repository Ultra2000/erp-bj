<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'sales_count',
        'total_sales',
        'total_cash',
        'total_card',
        'total_mobile',
        'total_other',
        'notes',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_card' => 'decimal:2',
        'total_mobile' => 'decimal:2',
        'total_other' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Vérifie si la session est ouverte
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Ouvre une nouvelle session de caisse
     */
    public static function openSession(int $companyId, int $userId, float $openingAmount = 0): self
    {
        return static::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'opening_amount' => $openingAmount,
            'opened_at' => now(),
            'status' => 'open',
        ]);
    }

    /**
     * Ferme la session de caisse
     */
    public function closeSession(float $closingAmount, ?string $notes = null): self
    {
        $this->recalculate();
        
        $this->update([
            'closing_amount' => $closingAmount,
            'difference' => $closingAmount - $this->expected_amount,
            'notes' => $notes,
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        return $this;
    }

    /**
     * Recalcule les totaux de la session
     */
    public function recalculate(): self
    {
        // Bypasser les global scopes (BelongsToCompany, WarehouseScope) pour compter
        // toutes les ventes liées à cette session sans filtrage contextuel
        $sales = Sale::withoutGlobalScopes()
            ->where('cash_session_id', $this->id)
            ->where('status', 'completed')
            ->get();
        
        $this->sales_count = $sales->count();
        $this->total_sales = $sales->sum('total');
        
        // Ventiler les montants par mode de paiement (y compris paiements mixtes)
        $totalCash = 0;
        $totalCard = 0;
        $totalMobile = 0;
        $totalOther = 0;
        
        foreach ($sales as $sale) {
            if ($sale->payment_method === 'mixed' && $sale->payment_details) {
                $details = is_array($sale->payment_details) ? $sale->payment_details : json_decode($sale->payment_details, true);
                $totalCash += floatval($details['cash'] ?? 0);
                $totalCard += floatval($details['card'] ?? 0);
                $totalMobile += floatval($details['mobile'] ?? 0);
            } elseif ($sale->payment_method === 'cash') {
                $totalCash += floatval($sale->total);
            } elseif ($sale->payment_method === 'card') {
                $totalCard += floatval($sale->total);
            } elseif ($sale->payment_method === 'mobile') {
                $totalMobile += floatval($sale->total);
            } else {
                $totalOther += floatval($sale->total);
            }
        }
        
        $this->total_cash = $totalCash;
        $this->total_card = $totalCard;
        $this->total_mobile = $totalMobile;
        $this->total_other = $totalOther;
        
        // Montant attendu = fond de caisse + ventes espèces
        $this->expected_amount = $this->opening_amount + $this->total_cash;
        
        $this->save();
        
        return $this;
    }

    /**
     * Récupère la session ouverte pour un utilisateur
     */
    public static function getOpenSession(int $companyId, int $userId): ?self
    {
        return static::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Total des ventes de la session
     */
    public function getTotalAttribute(): float
    {
        return $this->total_cash + $this->total_card + $this->total_mobile + $this->total_other;
    }
}
