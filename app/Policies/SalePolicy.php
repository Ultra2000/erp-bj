<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy extends BasePolicy
{
    protected string $module = 'sales';

    /**
     * Un caissier peut modifier une vente en attente de son entrepôt
     */
    public function update(User $user, $sale): bool
    {
        // Permission standard
        if ($user->hasPermission("{$this->module}.update") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Un caissier peut modifier une vente en attente de son entrepôt
        if ($user->isCashier() && $sale->status === 'pending') {
            // Vérifier que la vente est dans un entrepôt accessible
            if ($sale->warehouse_id && $user->hasAccessToWarehouse($sale->warehouse_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Un caissier peut créer des ventes dans son entrepôt
     */
    public function create(User $user): bool
    {
        if ($user->hasPermission("{$this->module}.create") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Un caissier peut toujours créer des ventes
        return $user->isCashier();
    }

    /**
     * Un caissier peut voir les ventes de son entrepôt
     */
    public function view(User $user, $sale): bool
    {
        if ($user->hasPermission("{$this->module}.view") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Un caissier peut voir les ventes de son entrepôt
        if ($user->isCashier() && $sale->warehouse_id) {
            return $user->hasAccessToWarehouse($sale->warehouse_id);
        }

        return false;
    }

    /**
     * Un caissier peut voir la liste des ventes
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasPermission("{$this->module}.view") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Un caissier peut voir la liste des ventes
        return $user->isCashier();
    }
}
