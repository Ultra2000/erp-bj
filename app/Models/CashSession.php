<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
     * Recalcule les totaux de la session (ventes + encaissements)
     */
    public function recalculate(): self
    {
        $sales = Sale::withoutGlobalScopes()
            ->where('cash_session_id', $this->id)
            ->where('status', 'completed')
            ->get();

        $sessionSaleIds = $sales->pluck('id')->toArray();

        $collectionPayments = $this->getCollectionPayments($sessionSaleIds);

        $this->sales_count = $sales->count() + $collectionPayments->unique('payable_id')->count();
        $this->total_sales = $sales->sum('total') + $collectionPayments->sum('amount');

        $totalCash = 0;
        $totalCard = 0;
        $totalMobile = 0;
        $totalOther = 0;

        foreach ($sales as $sale) {
            $saleAmount = floatval($sale->amount_paid ?? $sale->total);

            if ($sale->payment_method === 'mixed' && $sale->payment_details) {
                $details = is_array($sale->payment_details) ? $sale->payment_details : json_decode($sale->payment_details, true);
                $totalCash += floatval($details['cash'] ?? 0);
                $totalCard += floatval($details['card'] ?? 0);
                $totalMobile += floatval($details['mobile'] ?? 0);
            } elseif ($sale->payment_method === 'cash') {
                $totalCash += $saleAmount;
            } elseif ($sale->payment_method === 'card') {
                $totalCard += $saleAmount;
            } elseif ($sale->payment_method === 'mobile') {
                $totalMobile += $saleAmount;
            } else {
                $totalOther += $saleAmount;
            }
        }

        foreach ($collectionPayments as $payment) {
            $amount = floatval($payment->amount);
            match ($payment->payment_method) {
                'cash' => $totalCash += $amount,
                'card' => $totalCard += $amount,
                'mobile' => $totalMobile += $amount,
                default => $totalOther += $amount,
            };
        }

        $this->total_cash = $totalCash;
        $this->total_card = $totalCard;
        $this->total_mobile = $totalMobile;
        $this->total_other = $totalOther;

        $this->expected_amount = $this->opening_amount + $this->total_cash;

        $this->save();

        return $this;
    }

    /**
     * Paiements d'encaissement : paiements enregistrés dans cette session
     * pour des factures créées dans une autre session.
     */
    public function getCollectionPayments(?array $excludeSaleIds = null): Collection
    {
        if ($excludeSaleIds === null) {
            $excludeSaleIds = Sale::withoutGlobalScopes()
                ->where('cash_session_id', $this->id)
                ->where('status', 'completed')
                ->pluck('id')
                ->toArray();
        }

        $query = Payment::withoutGlobalScopes()
            ->where('cash_session_id', $this->id)
            ->where('payable_type', Sale::class);

        if (!empty($excludeSaleIds)) {
            $query->whereNotIn('payable_id', $excludeSaleIds);
        }

        return $query->get();
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
