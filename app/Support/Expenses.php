<?php

namespace App\Support;

use App\Models\User;

/**
 * Règles d'accès du module Dépenses.
 */
class Expenses
{
    public static function canView(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user && (
            $user->is_super_admin
            || $user->isAdmin()
            || $user->hasPermission('expenses.view')
            || $user->hasPermission('expenses.manage')
        );
    }

    public static function canManage(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user && (
            $user->is_super_admin
            || $user->isAdmin()
            || $user->hasPermission('expenses.manage')
        );
    }
}
