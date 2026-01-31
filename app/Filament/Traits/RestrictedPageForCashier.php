<?php

namespace App\Filament\Traits;

/**
 * Trait pour restreindre l'accès des caissiers aux pages Filament
 * Les caissiers n'ont accès qu'au Point de Vente et Ventes
 */
trait RestrictedPageForCashier
{
    /**
     * Vérifie si l'utilisateur est un caissier (avec restriction warehouse)
     */
    protected static function isCashierUser(): bool
    {
        $user = auth()->user();
        return $user && $user->hasWarehouseRestriction();
    }

    /**
     * Cache la page dans la navigation pour les caissiers
     */
    public static function shouldRegisterNavigation(): bool
    {
        if (static::isCashierUser()) {
            return false;
        }

        // Comportement par défaut si la classe parente a sa propre logique
        if (method_exists(parent::class, 'shouldRegisterNavigation')) {
            return parent::shouldRegisterNavigation();
        }

        return true;
    }

    /**
     * Empêche l'accès direct à la page pour les caissiers
     */
    public static function canAccess(): bool
    {
        if (static::isCashierUser()) {
            return false;
        }

        // Comportement par défaut si la classe parente a sa propre logique
        if (method_exists(parent::class, 'canAccess')) {
            return parent::canAccess();
        }

        return true;
    }
}
