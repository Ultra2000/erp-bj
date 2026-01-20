<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Scope global pour filtrer automatiquement les données par entrepôt utilisateur
 * 
 * Ce scope s'applique automatiquement aux modèles qui l'utilisent.
 * Les admins et super admins ne sont pas affectés (voient tout).
 * Les utilisateurs avec des entrepôts assignés ne voient que leurs données.
 */
class WarehouseScope implements Scope
{
    /**
     * Le nom de la colonne warehouse_id dans le modèle
     */
    protected string $warehouseColumn;

    public function __construct(string $warehouseColumn = 'warehouse_id')
    {
        $this->warehouseColumn = $warehouseColumn;
    }

    /**
     * Applique le scope au builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Ne pas appliquer si pas d'utilisateur connecté
        $user = Auth::user();
        
        if (!$user) {
            return;
        }

        // Ne pas appliquer si super admin
        if ($user->is_super_admin) {
            return;
        }

        // Ne pas appliquer si admin de l'entreprise
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }

        // Récupérer les IDs des entrepôts accessibles
        $warehouseIds = $user->accessibleWarehouseIds();

        // Si null (pas de restriction) ou vide (pas d'entrepôt assigné), ne pas filtrer
        if ($warehouseIds === null) {
            return;
        }

        // Si l'utilisateur n'a pas d'entrepôt assigné, ne rien montrer
        if (empty($warehouseIds)) {
            $builder->whereRaw('1 = 0'); // Aucun résultat
            return;
        }

        // Filtrer par les entrepôts de l'utilisateur
        $builder->whereIn($model->getTable() . '.' . $this->warehouseColumn, $warehouseIds);
    }
}
