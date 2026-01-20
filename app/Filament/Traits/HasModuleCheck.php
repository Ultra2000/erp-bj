<?php

namespace App\Filament\Traits;

use Filament\Facades\Filament;

trait HasModuleCheck
{
    /**
     * Retourne le nom du module requis pour cette ressource
     * À surcharger dans chaque ressource qui l'utilise
     */
    protected static function getRequiredModule(): ?string
    {
        return static::$requiredModule ?? null;
    }

    /**
     * Vérifie si la ressource doit être affichée
     * En fonction des modules activés pour l'entreprise courante
     */
    public static function shouldRegisterNavigation(): bool
    {
        $module = static::getRequiredModule();
        
        if (!$module) {
            return true;
        }

        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            return true;
        }

        return $tenant->isModuleEnabled($module);
    }

    /**
     * Vérifie si l'utilisateur peut accéder à cette ressource
     */
    public static function canAccess(): bool
    {
        $module = static::getRequiredModule();
        
        if (!$module) {
            return true;
        }

        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            return true;
        }

        return $tenant->isModuleEnabled($module);
    }
}
