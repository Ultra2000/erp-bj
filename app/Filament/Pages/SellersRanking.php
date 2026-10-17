<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class SellersRanking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Classement vendeurs';

    protected static ?string $title = 'Classement des vendeurs';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.sellers-ranking';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->isAdmin() || $user->hasPermission('reports.view'));
    }

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date = now()->toDateString();
    }

    public function getCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn () => route('reports.sellers', [
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
     * Classement des vendeurs sur une période (page + export PDF).
     */
    public static function buildData(int $companyId, ?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

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

        $biggest = (clone $base)->orderByDesc('total')->first();

        return [
            'sellers' => $sellers,
            'total_ca' => (float) $rows->sum('ca'),
            'total_sales' => (int) $rows->sum('nb'),
            'start' => $start,
            'end' => $end,
            'biggest' => $biggest ? [
                'amount' => (float) $biggest->total,
                'seller' => $names[$biggest->created_by] ?? ($biggest->createdBy?->name ?? '—'),
                'invoice' => $biggest->invoice_number,
                'date' => Carbon::parse($biggest->created_at)->timezone(config('app.display_timezone', 'Africa/Porto-Novo'))->format('d/m/Y'),
            ] : null,
        ];
    }
}
