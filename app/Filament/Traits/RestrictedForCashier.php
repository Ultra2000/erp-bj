<?php

namespace App\Filament\Traits;

trait RestrictedForCashier
{
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

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->is_super_admin || $user->isAdmin()) return true;

        $module = static::getPermissionModule();
        if (!$module) return true;

        return $user->hasPermission("{$module}.view")
            || $user->hasPermission("{$module}.manage");
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (property_exists(static::class, 'shouldRegisterNavigation')) {
            $reflection = new \ReflectionProperty(static::class, 'shouldRegisterNavigation');
            if ($reflection->getDeclaringClass()->getName() === static::class) {
                if ($reflection->getValue() === false) {
                    return false;
                }
            }
        }

        return static::canAccess();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::userHasPermission('create');
    }

    public static function canEdit($record): bool
    {
        return static::userHasPermission('edit');
    }

    public static function canDelete($record): bool
    {
        return static::userHasPermission('delete');
    }

    protected static function userHasPermission(string $action): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->is_super_admin || $user->isAdmin()) return true;

        $module = static::getPermissionModule();
        if (!$module) return true;

        return $user->hasPermission("{$module}.{$action}")
            || $user->hasPermission("{$module}.manage");
    }
}
