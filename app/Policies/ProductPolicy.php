<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy extends BasePolicy
{
    protected string $module = 'products';

    /**
     * Un utilisateur avec entrepôt assigné peut voir les produits
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasPermission("{$this->module}.view") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Un caissier peut voir les produits
        if ($user->isCashier()) {
            return true;
        }

        // Si l'utilisateur a un entrepôt assigné, il peut voir les produits
        $warehouseIds = $user->accessibleWarehouseIds();
        return !empty($warehouseIds);
    }

    /**
     * Un utilisateur avec entrepôt assigné peut voir un produit
     */
    public function view(User $user, $product): bool
    {
        if ($user->hasPermission("{$this->module}.view") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Un caissier peut voir les produits
        if ($user->isCashier()) {
            return true;
        }

        // Si l'utilisateur a un entrepôt assigné
        $warehouseIds = $user->accessibleWarehouseIds();
        return !empty($warehouseIds);
    }
}
