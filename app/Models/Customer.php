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
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
