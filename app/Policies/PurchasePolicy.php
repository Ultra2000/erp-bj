<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy extends BasePolicy
{
    protected string $module = 'purchases';

    /**
     * Un utilisateur peut modifier un achat en attente de son entrepôt
     */
    public function update(User $user, $purchase): bool
    {
        // Permission standard
        if ($user->hasPermission("{$this->module}.update") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Vérifier si l'achat est dans un entrepôt accessible et en attente
        if ($purchase->status === 'pending' && $purchase->warehouse_id) {
            return $user->hasAccessToWarehouse($purchase->warehouse_id);
        }

        return false;
    }

    /**
     * Un utilisateur peut créer des achats dans son entrepôt
     */
    public function create(User $user): bool
    {
        if ($user->hasPermission("{$this->module}.create") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Si l'utilisateur a un entrepôt assigné, il peut créer des achats
        $warehouseIds = $user->accessibleWarehouseIds();
        return !empty($warehouseIds);
    }

    /**
     * Un utilisateur peut voir les achats de son entrepôt
     */
    public function view(User $user, $purchase): bool
    {
        if ($user->hasPermission("{$this->module}.view") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Vérifier si l'achat est dans un entrepôt accessible
        if ($purchase->warehouse_id) {
            return $user->hasAccessToWarehouse($purchase->warehouse_id);
        }

        return false;
    }

    /**
     * Un utilisateur peut voir la liste des achats
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasPermission("{$this->module}.view") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Si l'utilisateur a un entrepôt assigné, il peut voir la liste
        $warehouseIds = $user->accessibleWarehouseIds();
        return !empty($warehouseIds);
    }
}
