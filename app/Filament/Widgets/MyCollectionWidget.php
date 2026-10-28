<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\SavingsTransaction;
use App\Support\Tontine;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MyCollectionWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Tontine::canView();
    }

    protected function getStats(): array
    {
        $companyId = Filament::getTenant()?->id;
        $currency = Filament::getTenant()->currency ?? 'FCFA';
        $uid = auth()->id();
        $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' ' . $currency;

        $startDay = now()->startOfDay();
        $startMonth = now()->startOfMonth();

        $myDeposits = SavingsTransaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('type', 'deposit')
            ->where('created_by', $uid);

        $todayAmount = (int) (clone $myDeposits)->where('created_at', '>=', $startDay)->sum('amount');
        $todayCount = (clone $myDeposits)->where('created_at', '>=', $startDay)->count();
        $monthAmount = (int) (clone $myDeposits)->where('created_at', '>=', $startMonth)->sum('amount');

        $zoneIds = Tontine::visibleZoneIds();
        $savers = Customer::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('savings_balance', '>', 0)
            ->when($zoneIds !== null, fn ($q) => $q->whereIn('zone_id', $zoneIds))
            ->count();

        return [
            Stat::make('Ma collecte aujourd\'hui', $fmt($todayAmount))
                ->description($todayCount . ' dépôt(s)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Ma collecte ce mois', $fmt($monthAmount))
                ->color('primary'),
            Stat::make('Épargnants', number_format($savers, 0, ',', ' '))
                ->description('dans mon périmètre')
                ->color('gray'),
        ];
    }
}
