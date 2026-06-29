<?php

namespace App\Filament\Traits;

/**
 * Trait pour restreindre l'accès des caissiers aux pages Filament.
 * Utilise le rôle réel (isCashier) au lieu de hasWarehouseRestriction().
 */
trait RestrictedPageForCashier
{
    protected static function isCashierUser(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        if ($user->is_super_admin || $user->isAdmin()) {
            return false;
        }

        return $user->isCashier();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (static::isCashierUser()) {
            return false;
        }

        if (method_exists(parent::class, 'shouldRegisterNavigation')) {
            return parent::shouldRegisterNavigation();
        }

        return true;
    }

    public static function canAccess(): bool
    {
        if (static::isCashierUser()) {
            return false;
        }

        if (method_exists(parent::class, 'canAccess')) {
            return parent::canAccess();
        }

        return true;
    }
}
