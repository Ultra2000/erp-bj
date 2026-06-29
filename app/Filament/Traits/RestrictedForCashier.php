<?php

namespace App\Filament\Traits;

/**
 * Trait pour restreindre l'accès aux ressources pour les caissiers.
 * Utilise le rôle réel (isCashier) et les permissions du rôle,
 * pas hasWarehouseRestriction() qui bloque tous les non-admins.
 */
trait RestrictedForCashier
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

    /**
     * Détermine le slug du module de permissions pour cette ressource.
     * Peut être surchargé dans la ressource si le slug ne correspond pas.
     */
    protected static function getPermissionModule(): ?string
    {
        $map = [
            \App\Filament\Resources\ProductResource::class => 'products',
            \App\Filament\Resources\PurchaseResource::class => 'purchases',
            \App\Filament\Resources\SupplierResource::class => 'suppliers',
            \App\Filament\Resources\StockTransferResource::class => 'transfers',
            \App\Filament\Resources\InventoryResource::class => 'inventory',
            \App\Filament\Resources\WarehouseResource::class => 'warehouses',
            \App\Filament\Resources\QuoteResource::class => 'quotes',
            \App\Filament\Resources\DeliveryNoteResource::class => 'deliveries',
            \App\Filament\Resources\PaymentResource::class => 'sales',
            \App\Filament\Resources\AccountingEntryResource::class => 'accounting',
            \App\Filament\Resources\AccountingCategoryResource::class => 'accounting',
            \App\Filament\Resources\AccountingRuleResource::class => 'accounting',
            \App\Filament\Resources\AccountingSettingResource::class => 'accounting',
            \App\Filament\Resources\BankAccountResource::class => 'banking',
            \App\Filament\Resources\BankTransactionResource::class => 'banking',
            \App\Filament\Resources\EmployeeResource::class => 'employees',
            \App\Filament\Resources\LeaveRequestResource::class => 'hr',
            \App\Filament\Resources\ScheduleResource::class => 'hr',
            \App\Filament\Resources\AttendanceLogResource::class => 'hr',
            \App\Filament\Resources\CommissionResource::class => 'sales',
            \App\Filament\Resources\RecurringOrderResource::class => 'sales',
            \App\Filament\Resources\UserResource::class => 'users',
            \App\Filament\Resources\RoleResource::class => 'roles',
            \App\Filament\Resources\InvitationResource::class => 'users',
            \App\Filament\Resources\ActivityLogResource::class => 'settings',
        ];

        return $map[static::class] ?? null;
    }

    protected static function userHasModuleAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        if ($user->is_super_admin || $user->isAdmin()) {
            return true;
        }

        if ($user->isCashier()) {
            return false;
        }

        $module = static::getPermissionModule();
        if (!$module) {
            return true;
        }

        return $user->hasPermission("{$module}.view");
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (property_exists(static::class, 'shouldRegisterNavigation')) {
            $reflection = new \ReflectionProperty(static::class, 'shouldRegisterNavigation');
            if ($reflection->getDeclaringClass()->getName() === static::class) {
                $value = $reflection->getValue();
                if ($value === false) {
                    return false;
                }
            }
        }

        return static::userHasModuleAccess();
    }

    public static function canViewAny(): bool
    {
        return static::userHasModuleAccess();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->is_super_admin || $user->isAdmin()) return true;
        if ($user->isCashier()) return false;

        $module = static::getPermissionModule();
        return $module ? $user->hasPermission("{$module}.create") : true;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->is_super_admin || $user->isAdmin()) return true;
        if ($user->isCashier()) return false;

        $module = static::getPermissionModule();
        return $module ? $user->hasPermission("{$module}.update") : true;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->is_super_admin || $user->isAdmin()) return true;
        if ($user->isCashier()) return false;

        $module = static::getPermissionModule();
        return $module ? $user->hasPermission("{$module}.delete") : true;
    }
}
