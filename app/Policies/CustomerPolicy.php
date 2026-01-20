<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy extends BasePolicy
{
    protected string $module = 'customers';

    /**
     * Un caissier peut voir les clients pour les ventes
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasPermission("{$this->module}.view") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        // Un caissier peut voir les clients
        return $user->isCashier();
    }

    /**
     * Un caissier peut voir un client
     */
    public function view(User $user, $customer): bool
    {
        if ($user->hasPermission("{$this->module}.view") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        return $user->isCashier();
    }

    /**
     * Un caissier peut créer des clients (pour les ventes)
     */
    public function create(User $user): bool
    {
        if ($user->hasPermission("{$this->module}.create") || $user->hasPermission("{$this->module}.manage")) {
            return true;
        }

        return $user->isCashier();
    }
}
