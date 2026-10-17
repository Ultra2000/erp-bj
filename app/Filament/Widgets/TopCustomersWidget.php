<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Sale;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class TopCustomersWidget extends Widget
{
    protected static string $view = 'filament.widgets.top-customers';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->isAdmin() || $user->hasPermission('reports.view'));
    }

    protected function getViewData(): array
    {
        $companyId = Filament::getTenant()?->id;
        $start = now()->startOfYear();
        $end = now()->endOfYear();

        // Exclure le(s) client(s) comptoir anonyme(s) du classement "ambassadeurs"
        $walkInIds = Customer::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(fn ($q) => $q->where('email', 'like', 'walkin@%')->orWhere('name', 'Client comptoir'))
            ->pluck('id')
            ->all();

        $rows = Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'credit_note'))
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('customer_id')
            ->when($walkInIds, fn ($q) => $q->whereNotIn('customer_id', $walkInIds))
            ->selectRaw('customer_id, COUNT(*) as nb, COALESCE(SUM(total), 0) as ca, MAX(created_at) as last_at')
            ->groupBy('customer_id')
            ->orderByDesc('ca')
            ->limit(10)
            ->get();

        $customers = Customer::whereIn('id', $rows->pluck('customer_id'))->pluck('name', 'id');

        $top = $rows->map(fn ($r, $i) => [
            'name' => $customers[$r->customer_id] ?? 'Client #' . $r->customer_id,
            'nb' => (int) $r->nb,
            'ca' => (float) $r->ca,
            'ambassador' => $i < 3, // les 3 premiers = ambassadeurs
        ])->values()->all();

        return [
            'year' => $start->format('Y'),
            'customers' => $top,
            'currency' => Filament::getTenant()->currency ?? 'FCFA',
        ];
    }
}
