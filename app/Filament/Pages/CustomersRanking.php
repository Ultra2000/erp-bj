<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class CustomersRanking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Classement clients';

    protected static ?string $title = 'Classement des clients';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.customers-ranking';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->isAdmin() || $user->hasPermission('reports.view'));
    }

    public function getCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date = now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn () => route('reports.customers', [
                    'company' => $this->getCompanyId(),
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    protected function getViewData(): array
    {
        return array_merge(
            static::buildData($this->getCompanyId(), $this->start_date, $this->end_date),
            ['currency' => Filament::getTenant()->currency ?? 'FCFA'],
        );
    }

    /**
     * Classement des clients sur une période (page + export PDF).
     * Les ventes « comptoir » (clients anonymes) sont exclues.
     */
    public static function buildData(int $companyId, ?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        // Clients « comptoir » à exclure du classement.
        $walkInIds = Customer::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(fn ($q) => $q->where('email', 'like', 'walkin@%')->orWhere('name', 'Client comptoir'))
            ->pluck('id')
            ->all();

        $base = Sale::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'credit_note'))
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('customer_id')
            ->when($walkInIds, fn ($q) => $q->whereNotIn('customer_id', $walkInIds));

        $rows = (clone $base)
            ->selectRaw('customer_id, COUNT(*) as nb, COALESCE(SUM(total), 0) as ca, COALESCE(MAX(total), 0) as max_sale, MAX(created_at) as last_at')
            ->groupBy('customer_id')
            ->orderByDesc('ca')
            ->get();

        $customers = Customer::withoutGlobalScopes()
            ->whereIn('id', $rows->pluck('customer_id'))
            ->get(['id', 'name', 'phone'])
            ->keyBy('id');

        $ranking = $rows->map(fn ($r) => [
            'name' => $customers[$r->customer_id]->name ?? 'Client #' . $r->customer_id,
            'phone' => $customers[$r->customer_id]->phone ?? null,
            'nb' => (int) $r->nb,
            'ca' => (float) $r->ca,
            'max_sale' => (float) $r->max_sale,
            'avg' => $r->nb > 0 ? (float) $r->ca / $r->nb : 0,
            'last_at' => $r->last_at ? Carbon::parse($r->last_at)->timezone(config('app.display_timezone', 'Africa/Porto-Novo'))->format('d/m/Y') : '—',
        ])->values()->all();

        $biggest = (clone $base)->orderByDesc('total')->first();

        return [
            'customers' => $ranking,
            'total_ca' => (float) $rows->sum('ca'),
            'total_sales' => (int) $rows->sum('nb'),
            'clients_count' => $rows->count(),
            'start' => $start,
            'end' => $end,
            'biggest' => $biggest ? [
                'amount' => (float) $biggest->total,
                'client' => $customers[$biggest->customer_id]->name ?? ($biggest->customer?->name ?? '—'),
                'invoice' => $biggest->invoice_number,
                'date' => Carbon::parse($biggest->created_at)->timezone(config('app.display_timezone', 'Africa/Porto-Novo'))->format('d/m/Y'),
            ] : null,
        ];
    }
}
