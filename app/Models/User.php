<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active', 'is_super_admin'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('users')
            ->setDescriptionForEvent(fn(string $eventName) => "Utilisateur {$eventName}")
            ->dontLogIfAttributesChangedOnly(['updated_at', 'password', 'remember_token']);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->companies;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->is_super_admin) {
            return true;
        }
        return $this->companies->contains($tenant);
    }

    /**
     * Vérifie si l'utilisateur est admin de l'entreprise courante
     * Utilise maintenant le système de rôles basé sur les tables
     */
    public function isAdmin(): bool
    {
        return $this->isAdminOf();
    }

    /**
     * Vérifie si l'utilisateur est manager
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager') || $this->hasRole('gestionnaire');
    }

    /**
     * Vérifie si l'utilisateur est un simple utilisateur
     */
    public function isUser(): bool
    {
        return $this->hasRole('user') || $this->hasRole('utilisateur');
    }

    /**
     * Vérifie si l'utilisateur est caissier
     */
    public function isCashier(): bool
    {
        return $this->hasRole('cashier') || $this->hasRole('caissier') || $this->hasRole('vendeur');
    }

    /**
     * Les invitations envoyées par cet utilisateur
     */
    public function sentInvitations()
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    /**
     * Filament: déterminer si l'utilisateur peut accéder à un panel.
     * Utilise maintenant le système de rôles basé sur les tables
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Bloquer l'accès si le compte est désactivé
        if (!$this->is_active) {
            return false;
        }

        if ($panel->getId() === 'superadmin') {
            return $this->is_super_admin;
        }

        if ($panel->getId() === 'admin') {
            // Les super admins ont toujours accès
            if ($this->is_super_admin) {
                return true;
            }
            // Tout utilisateur actif peut accéder au panel admin
            // (s'il n'a pas de company, il sera redirigé vers /admin/new)
            return true;
        }
        
        if ($panel->getId() === 'caisse') {
            // Admin ou caissier peuvent accéder à la caisse
            if ($this->is_super_admin) {
                return true;
            }
            return $this->isAdmin() || $this->isCashier();
        }
        
        return false;
    }

    /**
     * Récupère le nom d'affichage du rôle principal pour l'entreprise courante
     */
    public function getRoleDisplayAttribute(): string
    {
        $role = $this->currentRole();
        return $role ? $role->name : 'Aucun rôle';
    }

    /**
     * Vérifie si l'utilisateur peut gérer un module spécifique
     */
    public function canManage(string $module): bool
    {
        return $this->hasPermission("{$module}.manage") || 
               $this->hasPermission("{$module}.edit") || 
               $this->isAdmin();
    }

    /**
     * Vérifie si l'utilisateur peut voir un module spécifique
     */
    public function canView(string $module): bool
    {
        return $this->hasPermission("{$module}.view") || 
               $this->hasPermission("{$module}.manage") || 
               $this->isAdmin();
    }

    // ============================================
    // GESTION DES ENTREPÔTS / BOUTIQUES
    // ============================================

    /**
     * Les entrepôts/boutiques auxquels l'utilisateur a accès
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * Récupère l'entrepôt par défaut de l'utilisateur pour l'entreprise courante
     */
    public function defaultWarehouse(): ?Warehouse
    {
        $companyId = filament()->getTenant()?->id;
        
        if (!$companyId) {
            return null;
        }

        // D'abord chercher l'entrepôt marqué par défaut
        $default = $this->warehouses()
            ->where('company_id', $companyId)
            ->wherePivot('is_default', true)
            ->first();

        if ($default) {
            return $default;
        }

        // Sinon retourner le premier entrepôt assigné
        return $this->warehouses()
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * Récupère l'ID de l'entrepôt courant (pour le scope)
     * Retourne null si admin (pas de restriction)
     */
    public function currentWarehouseId(): ?int
    {
        // Les admins et super admins voient tout
        if ($this->is_super_admin || $this->isAdmin()) {
            return null;
        }

        return $this->defaultWarehouse()?->id;
    }

    /**
     * Récupère les IDs de tous les entrepôts accessibles
     * Retourne null si admin (pas de restriction)
     */
    public function accessibleWarehouseIds(): ?array
    {
        // Les admins et super admins voient tout
        if ($this->is_super_admin || $this->isAdmin()) {
            return null;
        }

        $companyId = filament()->getTenant()?->id;
        
        if (!$companyId) {
            return [];
        }

        return $this->warehouses()
            ->where('company_id', $companyId)
            ->pluck('warehouses.id')
            ->toArray();
    }

    /**
     * Vérifie si l'utilisateur a accès à un entrepôt spécifique
     */
    public function hasAccessToWarehouse(int $warehouseId): bool
    {
        // Les admins ont accès à tous les entrepôts
        if ($this->is_super_admin || $this->isAdmin()) {
            return true;
        }

        return $this->warehouses()->where('warehouses.id', $warehouseId)->exists();
    }

    /**
     * Vérifie si l'utilisateur est restreint à des entrepôts spécifiques
     * Un utilisateur non-admin est TOUJOURS restreint (même sans entrepôt assigné)
     */
    public function hasWarehouseRestriction(): bool
    {
        // Les admins et super admins n'ont pas de restriction
        if ($this->is_super_admin || $this->isAdmin()) {
            return false;
        }

        // Tous les autres utilisateurs sont restreints
        // S'ils n'ont pas d'entrepôt assigné, ils ne voient rien
        return true;
    }

    /**
     * Définit l'entrepôt par défaut pour l'utilisateur
     */
    public function setDefaultWarehouse(int $warehouseId): void
    {
        // Retirer le flag par défaut des autres entrepôts
        $this->warehouses()->updateExistingPivot(
            $this->warehouses()->pluck('warehouses.id')->toArray(),
            ['is_default' => false]
        );

        // Définir le nouveau par défaut
        if ($this->warehouses()->where('warehouses.id', $warehouseId)->exists()) {
            $this->warehouses()->updateExistingPivot($warehouseId, ['is_default' => true]);
        }
    }

    /**
     * Retourne le nom d'affichage pour l'opérateur e-MCeF
     * Format: "Nom Utilisateur - Boutique"
     */
    public function getEmcefOperatorName(): string
    {
        $warehouse = $this->defaultWarehouse();
        
        if ($warehouse) {
            return "{$this->name} - {$warehouse->name}";
        }

        return $this->name;
    }
}
