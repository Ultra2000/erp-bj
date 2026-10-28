<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'city',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Épargnants / clients rattachés à cette zone. */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /** Tontiniers (agents) affectés à cette zone. */
    public function tontiniers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tontinier_zone', 'zone_id', 'user_id')->withTimestamps();
    }
}
