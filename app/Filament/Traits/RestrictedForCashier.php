<?php

namespace App\Filament\Traits;

/**
 * Trait pour restreindre l'accès aux ressources pour les caissiers
 * Les caissiers n'ont accès qu'aux sections Point de Vente et Ventes
 */
trait RestrictedForCashier
{
    /**
     * Vérifie si l'utilisateur actuel est un caissier (a une restriction entrepôt)
     */
    protected static function isCashierUser(): bool
    {
        $user = auth()->user();
        return $user && $user->hasWarehouseRestriction();
    }

    /**
     * Cacher la navigation pour les caissiers
     */
    public static function shouldRegisterNavigation(): bool
    {
        return !static::isCashierUser();
    }

    /**
     * Empêcher l'accès pour les caissiers
     */
    public static function canViewAny(): bool
    {
        return !static::isCashierUser();
    }

    public static function canCreate(): bool
    {
        return !static::isCashierUser();
    }

    public static function canEdit($record): bool
    {
        return !static::isCashierUser();
    }

    public static function canDelete($record): bool
    {
        return !static::isCashierUser();
    }
}
