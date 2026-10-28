<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'expense_category_id',
        'user_id',
        'beneficiary',
        'amount',
        'payment_method',
        'reference',
        'notes',
        'receipt_path',
        'spent_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'spent_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            if (empty($expense->created_by) && auth()->check()) {
                $expense->created_by = auth()->id();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /** Bénéficiaire (utilisateur) — ex. salaire versé à un employé/utilisateur. */
    public function beneficiaryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Libellé du bénéficiaire (utilisateur si présent, sinon texte libre). */
    public function getBeneficiaryLabelAttribute(): ?string
    {
        return $this->beneficiaryUser?->name ?? $this->beneficiary;
    }
}
