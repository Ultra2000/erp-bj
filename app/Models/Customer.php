<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, BelongsToCompany, LogsActivity;

    protected static function boot()
    {
        parent::boot();

        // Synchroniser registration_number → tax_number pour compatibilité e-MCeF
        static::saving(function ($customer) {
            if ($customer->registration_number && empty($customer->tax_number)) {
                $customer->tax_number = $customer->registration_number;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'address'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('customers')
            ->setDescriptionForEvent(fn(string $eventName) => "Client {$eventName}")
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    protected $fillable = [
        'company_id',
        'name',
        'registration_number',
        'siret',
        'tax_number',
        'email',
        'phone',
        'address',
        'zip_code',
        'city',
        'country',
        'country_code',
        'notes',
        'customer_type',
        'savings_balance',
    ];

    protected $casts = [
        'savings_balance' => 'integer',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function savingsTransactions()
    {
        return $this->hasMany(SavingsTransaction::class)->latest();
    }

    // ============================================
    // TONTINE / ÉPARGNE (solde porte-monnaie)
    // ============================================

    /**
     * Enregistre un mouvement d'épargne de façon atomique (verrou de ligne),
     * met à jour le solde et journalise. $signedAmount : + crédite, - débite.
     * Lève une exception si le solde deviendrait négatif.
     */
    protected function recordSavings(string $type, int $signedAmount, array $meta = []): SavingsTransaction
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($type, $signedAmount, $meta) {
            $locked = static::withoutGlobalScopes()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            $newBalance = (int) $locked->savings_balance + $signedAmount;
            if ($newBalance < 0) {
                throw new \RuntimeException('Solde d\'épargne insuffisant.');
            }

            $locked->savings_balance = $newBalance;
            $locked->saveQuietly();
            $this->savings_balance = $newBalance;

            return SavingsTransaction::create(array_merge([
                'company_id' => $this->company_id,
                'customer_id' => $this->getKey(),
                'type' => $type,
                'amount' => $signedAmount,
                'balance_after' => $newBalance,
                'created_by' => auth()->id(),
            ], $meta));
        });
    }

    public function deposit(int $amount, array $meta = []): SavingsTransaction
    {
        return $this->recordSavings('deposit', abs($amount), $meta);
    }

    public function withdraw(int $amount, array $meta = []): SavingsTransaction
    {
        return $this->recordSavings('withdrawal', -abs($amount), $meta);
    }

    public function spendSavings(int $amount, array $meta = []): SavingsTransaction
    {
        return $this->recordSavings('purchase', -abs($amount), $meta);
    }

    /**
     * Écriture d'ajustement (correction / contre-passation). Montant signé :
     * positif pour recréditer, négatif pour débiter.
     */
    public function adjust(int $signedAmount, array $meta = []): SavingsTransaction
    {
        return $this->recordSavings('adjustment', $signedAmount, $meta);
    }
}
