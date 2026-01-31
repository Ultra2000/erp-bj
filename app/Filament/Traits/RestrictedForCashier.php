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
     * Respecte aussi $shouldRegisterNavigation = false si défini dans la classe
     */
    public static function shouldRegisterNavigation(): bool
    {
        // Respecter la propriété de la classe si elle est définie à false
        if (property_exists(static::class, 'shouldRegisterNavigation')) {
            $reflection = new \ReflectionProperty(static::class, 'shouldRegisterNavigation');
            if ($reflection->getDeclaringClass()->getName() === static::class) {
                $value = $reflection->getValue();
                if ($value === false) {
                    return false;
                }
            }
        }
        
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
