<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class TopSellersWidget extends Widget
{
    protected static string $view = 'filament.widgets.top-sellers';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->isAdmin() || $user->hasPermission('reports.view'));
    }

    protected function getViewData(): array
    {
        $companyId = Filament::getTenant()?->id;
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        // Ventes finalisées du mois (hors avoirs), toutes boutiques de l'entreprise.
        $base = Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'credit_note'))
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('created_by');

        $rows = (clone $base)
            ->selectRaw('created_by, COUNT(*) as nb, COALESCE(SUM(total), 0) as ca, COALESCE(MAX(total), 0) as max_sale')
            ->groupBy('created_by')
            ->orderByDesc('ca')
            ->get();

        $names = User::whereIn('id', $rows->pluck('created_by'))->pluck('name', 'id');

        $sellers = $rows->map(fn ($r) => [
            'name' => $names[$r->created_by] ?? 'Utilisateur #' . $r->created_by,
            'nb' => (int) $r->nb,
            'ca' => (float) $r->ca,
            'max_sale' => (float) $r->max_sale,
            'avg' => $r->nb > 0 ? (float) $r->ca / $r->nb : 0,
        ])->values()->all();

        // La plus grosse vente du mois + son vendeur
        $biggest = (clone $base)->orderByDesc('total')->first();

        return [
            'month' => ucfirst($start->locale('fr')->isoFormat('MMMM YYYY')),
            'sellers' => $sellers,
            'currency' => Filament::getTenant()->currency ?? 'FCFA',
            'biggest' => $biggest ? [
                'amount' => (float) $biggest->total,
                'seller' => $names[$biggest->created_by] ?? ($biggest->createdBy?->name ?? '—'),
                'invoice' => $biggest->invoice_number,
                'date' => Carbon::parse($biggest->created_at)->timezone(config('app.display_timezone', 'Africa/Porto-Novo'))->format('d/m/Y'),
            ] : null,
        ];
    }
}
