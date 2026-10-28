<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Filament\Widgets\ExpensesSummaryWidget;
use App\Support\Expenses;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    public function getTitle(): string
    {
        return 'Dépenses';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExpensesSummaryWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle dépense')
                ->visible(fn () => Expenses::canManage()),
            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->form([
                    Forms\Components\DatePicker::make('from')->label('Du')->default(now()->startOfMonth())->required(),
                    Forms\Components\DatePicker::make('until')->label('Au')->default(now())->required(),
                    Forms\Components\Select::make('category')
                        ->label('Catégorie (optionnel)')
                        ->options(fn () => \App\Models\ExpenseCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->placeholder('Toutes'),
                ])
                ->action(function (array $data) {
                    $url = route('reports.expenses', array_filter([
                        'company' => Filament::getTenant()?->id,
                        'from' => $data['from'],
                        'until' => $data['until'],
                        'category' => $data['category'] ?? null,
                    ]));
                    $this->js('window.open(' . json_encode($url) . ', "_blank")');
                }),
        ];
    }
}
