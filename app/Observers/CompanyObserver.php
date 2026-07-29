<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Warehouse;

class CompanyObserver
{
    /**
     * Les rôles par défaut à créer pour chaque nouvelle entreprise
     */
    protected array $defaultRoles = [
        'admin' => [
            'name' => 'Administrateur',
            'description' => 'Accès complet à toutes les fonctionnalités',
            'permissions' => '*',
        ],
        'manager' => [
            'name' => 'Gestionnaire',
            'description' => 'Gestion des opérations quotidiennes',
            'permissions' => [
                'products.view', 'products.create', 'products.edit', 'products.stock',
                'sales.view', 'sales.create', 'sales.edit',
                'purchases.view', 'purchases.create', 'purchases.edit',
                'customers.view', 'customers.create', 'customers.edit',
                'suppliers.view', 'suppliers.create', 'suppliers.edit',
                'quotes.view', 'quotes.create', 'quotes.edit',
                'deliveries.view', 'deliveries.create', 'deliveries.edit',
                'pos.access', 'pos.sell', 'pos.collect', 'pos.session', 'pos.reports',
                'warehouses.view', 'transfers.create', 'transfers.approve', 'inventory.manage',
                'employees.view', 'schedule.manage', 'leaves.manage', 'attendance.view',
                'reports.view',
            ],
        ],
        'cashier' => [
            'name' => 'Caissier',
            'description' => 'Encaissement et caisse',
            'permissions' => [
                'pos.access', 'pos.collect', 'pos.session',
            ],
        ],
        'vendeur' => [
            'name' => 'Vendeur',
            'description' => 'Ventes et relation client',
            'permissions' => [
                'products.view',
                'sales.view', 'sales.create',
                'customers.view', 'customers.create', 'customers.edit',
                'quotes.view', 'quotes.create',
                'deliveries.view',
                'pos.access', 'pos.sell',
            ],
        ],
        'magasinier' => [
            'name' => 'Magasinier',
            'description' => 'Gestion du stock et des entrepôts',
            'permissions' => [
                'products.view', 'products.stock',
                'purchases.view',
                'warehouses.view', 'transfers.create', 'inventory.manage',
            ],
        ],
        'user' => [
            'name' => 'Utilisateur',
            'description' => 'Accès limité en lecture',
            'permissions' => [
                'products.view',
                'sales.view',
                'customers.view',
            ],
            'is_default' => true,
        ],
    ];

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        $this->createDefaultRoles($company);
        $this->createDefaultWarehouse($company);
    }

    /**
     * Crée l'entrepôt par défaut pour une nouvelle entreprise
     */
    protected function createDefaultWarehouse(Company $company): void
    {
        // Le code entrepôt peut être unique globalement selon le schéma :
        // garantir l'unicité pour ne pas bloquer la création d'une nouvelle entreprise.
        $code = 'MAIN';
        if (Warehouse::where('code', $code)->exists()) {
            $code = 'MAIN-' . $company->id;
        }

        Warehouse::create([
            'company_id' => $company->id,
            'code' => $code,
            'name' => 'Entrepôt Principal',
            'type' => 'warehouse',
            'is_default' => true,
            'is_active' => true,
            'allow_negative_stock' => false,
            'is_pos_location' => true,
            'address' => $company->address,
            'city' => $company->city,
            'country' => $company->country ?? 'SN',
        ]);
    }

    /**
     * Crée les rôles par défaut pour une entreprise
     */
    protected function createDefaultRoles(Company $company): void
    {
        foreach ($this->defaultRoles as $slug => $roleData) {
            $role = Role::create([
                'company_id' => $company->id,
                'slug' => $slug,
                'name' => $roleData['name'],
                'description' => $roleData['description'],
                'is_default' => $roleData['is_default'] ?? false,
            ]);

            // Assigner les permissions
            if ($roleData['permissions'] === '*') {
                $role->permissions()->sync(Permission::pluck('id'));
            } else {
                $permissionIds = Permission::whereIn('slug', $roleData['permissions'])->pluck('id');
                $role->permissions()->sync($permissionIds);
            }
        }
    }

    /**
     * Handle the Company "deleting" event.
     *
     * Certaines tables (stock_movements, inventory_items, stock_transfer_items)
     * référencent products/warehouses en RESTRICT : elles bloquent la cascade
     * de suppression de l'entreprise. On les supprime d'abord.
     */
    public function deleting(Company $company): void
    {
        $id = $company->id;

        \DB::transaction(function () use ($id) {
            // Mouvements de stock (product_id / warehouse_id en RESTRICT)
            \DB::table('stock_movements')->where('company_id', $id)->delete();

            // Transferts + lignes (product_id en RESTRICT)
            $transferIds = \DB::table('stock_transfers')->where('company_id', $id)->pluck('id');
            if ($transferIds->isNotEmpty()) {
                \DB::table('stock_transfer_items')->whereIn('stock_transfer_id', $transferIds)->delete();
            }
            \DB::table('stock_transfers')->where('company_id', $id)->delete();

            // Inventaires + lignes (product_id / warehouse_id en RESTRICT)
            $inventoryIds = \DB::table('inventories')->where('company_id', $id)->pluck('id');
            if ($inventoryIds->isNotEmpty()) {
                \DB::table('inventory_items')->whereIn('inventory_id', $inventoryIds)->delete();
            }
            \DB::table('inventories')->where('company_id', $id)->delete();

            // Emplacements de stock produit (pivot)
            \DB::table('product_warehouse')->where('company_id', $id)->delete();
        });
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        // Les rôles et autres relations sont supprimés en cascade (foreign keys)
    }
}
