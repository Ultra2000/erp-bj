<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SalesChart;
use App\Filament\Widgets\StockAlert;
use App\Filament\Widgets\WarehouseOverview;
use App\Filament\Widgets\WarehouseStockSummary;
use App\Filament\Widgets\QuickActionsWidget;
use App\Models\Warehouse;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Facades\Filament;

class Dashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Tableau de bord';
    protected static ?string $title = 'Tableau de bord';
    protected static ?int $navigationSort = -2;
    protected static ?string $slug = '';
    protected static string $routePath = '/';

    protected static string $view = 'filament.pages.dashboard';

    public ?int $selectedWarehouse = null;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return !$user?->isCashier();
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user && $user->isCashier()) {
            $tenant = Filament::getTenant();
            if ($tenant) {
                redirect()->to(route('filament.admin.pages.cash-register-page', ['tenant' => $tenant->slug]));
                return;
            }
        }

        $this->selectedWarehouse = null;
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();
        $companyId = Filament::getTenant()?->id;

        if ($user && !$user->isCashier()) {
            return $form
                ->schema([
                    Select::make('selectedWarehouse')
                        ->label('Filtrer par entrepôt')
                        ->placeholder('Tous les entrepôts')
                        ->options(
                            Warehouse::where('company_id', $companyId)
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                        )
                        ->live()
                        ->afterStateUpdated(fn () => $this->dispatch('warehouse-filter-changed', warehouseId: $this->selectedWarehouse))
                ]);
        }

        return $form->schema([]);
    }

    protected function getCustomHeaderWidgets(): array
    {
        return [
            StatsOverview::make(['selectedWarehouse' => $this->selectedWarehouse]),
            SalesChart::make(['selectedWarehouse' => $this->selectedWarehouse]),
            StockAlert::make(['selectedWarehouse' => $this->selectedWarehouse]),
        ];
    }

    protected function getCustomFooterWidgets(): array
    {
        return [
            WarehouseOverview::make(['selectedWarehouse' => $this->selectedWarehouse]),
            WarehouseStockSummary::make(['selectedWarehouse' => $this->selectedWarehouse]),
        ];
    }

    public function getWidgets(): array
    {
        return array_merge(
            $this->getCustomHeaderWidgets(),
            $this->getCustomFooterWidgets()
        );
    }
} 