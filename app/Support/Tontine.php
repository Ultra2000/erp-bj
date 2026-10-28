<?php

namespace App\Support;

use App\Models\User;

/**
 * Règles d'accès centralisées pour le module Tontine / Épargne.
 */
class Tontine
{
    /**
     * Peut enregistrer des mouvements (dépôt / retrait / ajustement).
     * Admin/super, ou permission dédiée tontine.manage, ou pos.collect
     * (compatibilité : les caissiers/managers existants gardent l'accès).
     */
    public static function canManage(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user && (
            $user->is_super_admin
            || $user->isAdmin()
            || $user->hasPermission('tontine.manage')
            || $user->hasPermission('pos.collect')
        );
    }

    /**
     * Peut corriger / annuler un mouvement d'épargne (opération sensible).
     * Réservé aux administrateurs.
     */
    public static function canAdjust(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user && ($user->is_super_admin || $user->isAdmin());
    }

    /**
     * Peut consulter la vue d'ensemble / les mouvements d'épargne.
     */
    public static function canView(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user && (
            $user->is_super_admin
            || $user->isAdmin()
            || $user->hasPermission('reports.view')
            || $user->hasPermission('tontine.manage')
            || $user->hasPermission('pos.collect')
        );
    }
}
