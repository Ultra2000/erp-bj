<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Support\Expenses;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpensesSummaryWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Expenses::canView();
    }

    protected function getStats(): array
    {
        $currency = Filament::getTenant()->currency ?? 'FCFA';
        $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' ' . $currency;

        $startMonth = now()->startOfMonth();
        $startDay = now()->startOfDay();

        // Expense::query() est déjà filtré par entreprise (BelongsToCompany).
        $monthAmount = (int) Expense::query()->where('spent_at', '>=', $startMonth->toDateString())->sum('amount');
        $monthCount = Expense::query()->where('spent_at', '>=', $startMonth->toDateString())->count();
        $todayAmount = (int) Expense::query()->where('spent_at', '>=', $startDay->toDateString())->sum('amount');

        return [
            Stat::make('Dépenses ce mois', $fmt($monthAmount))
                ->description($monthCount . ' dépense(s)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),
            Stat::make('Dépenses aujourd\'hui', $fmt($todayAmount))
                ->color('warning'),
        ];
    }
}
