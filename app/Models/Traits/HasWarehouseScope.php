<?php

namespace App\Models\Traits;

use App\Models\Scopes\WarehouseScope;

/**
 * Trait pour appliquer automatiquement le filtrage par entrepôt utilisateur
 * 
 * Utilisation:
 * - Ajoutez `use HasWarehouseScope;` dans votre modèle
 * - Le modèle doit avoir une colonne `warehouse_id`
 * - Pour une colonne différente, surchargez la méthode `getWarehouseColumn()`
 */
trait HasWarehouseScope
{
    /**
     * Boot du trait - applique le scope automatiquement
     */
    public static function bootHasWarehouseScope(): void
    {
        static::addGlobalScope(new WarehouseScope(static::getWarehouseColumn()));
    }

    /**
     * Retourne le nom de la colonne warehouse_id
     * Peut être surchargée dans le modèle si différente
     */
    public static function getWarehouseColumn(): string
    {
        return 'warehouse_id';
    }

    /**
     * Scope pour désactiver temporairement le filtrage par entrepôt
     */
    public function scopeWithoutWarehouseScope($query)
    {
        return $query->withoutGlobalScope(WarehouseScope::class);
    }

    /**
     * Scope pour filtrer par un entrepôt spécifique
     */
    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->withoutGlobalScope(WarehouseScope::class)
            ->where(static::getWarehouseColumn(), $warehouseId);
    }

    /**
     * Scope pour filtrer par plusieurs entrepôts
     */
    public function scopeForWarehouses($query, array $warehouseIds)
    {
        return $query->withoutGlobalScope(WarehouseScope::class)
            ->whereIn(static::getWarehouseColumn(), $warehouseIds);
    }
}
