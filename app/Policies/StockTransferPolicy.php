<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy extends BasePolicy
{
    protected string $module = 'transfers';

    /**
     * Voir la liste des transferts
     */
    public function viewAny(User $user): bool
    {
        // Les utilisateurs avec accès entrepôt peuvent voir les transferts
        if ($user->hasWarehouseRestriction() && !empty($user->accessibleWarehouseIds())) {
            return true;
        }
        
        return parent::viewAny($user);
    }

    /**
     * Voir un transfert spécifique
     */
    public function view(User $user, $model): bool
    {
        // Utilisateur restreint: peut voir si source ou destination
        if ($user->hasWarehouseRestriction()) {
            return $user->hasAccessToWarehouse($model->source_warehouse_id) 
                || $user->hasAccessToWarehouse($model->destination_warehouse_id);
        }
        
        return parent::view($user, $model);
    }

    /**
     * Créer un transfert
     */
    public function create(User $user): bool
    {
        // Les utilisateurs avec accès entrepôt peuvent créer des transferts
        if ($user->hasWarehouseRestriction() && !empty($user->accessibleWarehouseIds())) {
            return true;
        }
        
        return parent::create($user);
    }

    /**
     * Modifier un transfert
     */
    public function update(User $user, $model): bool
    {
        // Seuls les transferts draft/pending peuvent être modifiés
        if (!in_array($model->status, ['draft', 'pending'])) {
            return false;
        }
        
        // Utilisateur restreint: doit avoir accès à l'entrepôt source
        if ($user->hasWarehouseRestriction()) {
            return $user->hasAccessToWarehouse($model->source_warehouse_id);
        }
        
        return parent::update($user, $model);
    }

    /**
     * Supprimer un transfert
     */
    public function delete(User $user, $model): bool
    {
        // Seuls les transferts draft peuvent être supprimés
        if ($model->status !== 'draft') {
            return false;
        }
        
        // Utilisateur restreint: doit avoir accès à l'entrepôt source
        if ($user->hasWarehouseRestriction()) {
            return $user->hasAccessToWarehouse($model->source_warehouse_id);
        }
        
        return parent::delete($user, $model);
    }
}
