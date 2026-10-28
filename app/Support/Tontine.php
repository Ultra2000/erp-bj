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
     * Peut enregistrer un dépôt (collecte). Inclut les tontiniers de terrain
     * (tontine.collect) en plus du personnel de bureau.
     */
    public static function canCollect(?User $user = null): bool
    {
        $user ??= auth()->user();

        return static::canManage($user) || ($user && $user->hasPermission('tontine.collect'));
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
     * Peut gérer les zones et les tontiniers. Réservé aux administrateurs.
     */
    public static function canAdminister(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user && ($user->is_super_admin || $user->isAdmin());
    }

    /**
     * Périmètre : un tontinier ne voit que les zones qui lui sont affectées ;
     * les autres rôles autorisés voient tout. Retourne null = pas de restriction.
     */
    public static function visibleZoneIds(?User $user = null): ?array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        // Admins / bureau : aucune restriction.
        if ($user->is_super_admin || $user->isAdmin() || static::canManage($user)) {
            return null;
        }

        // Tontinier : uniquement ses zones.
        if ($user->isTontinier()) {
            return $user->tontinierZoneIds();
        }

        return null;
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
            || $user->hasPermission('tontine.collect')
        );
    }
}
