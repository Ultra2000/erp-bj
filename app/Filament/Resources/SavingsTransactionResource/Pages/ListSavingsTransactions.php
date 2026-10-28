<?php

namespace App\Filament\Resources\SavingsTransactionResource\Pages;

use App\Filament\Concerns\HasTontineActions;
use App\Filament\Resources\SavingsTransactionResource;
use App\Models\SavingsTransaction;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListSavingsTransactions extends ListRecords
{
    use HasTontineActions;

    protected static string $resource = SavingsTransactionResource::class;

    public function getTitle(): string
    {
        return 'Mouvements d\'épargne';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->tontineDepositAction(),
            $this->tontineWithdrawAction(),

            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->form([
                    Forms\Components\DatePicker::make('from')->label('Du')->default(now()->startOfMonth())->required(),
                    Forms\Components\DatePicker::make('until')->label('Au')->default(now())->required(),
                    Forms\Components\Select::make('type')->label('Type (optionnel)')
                        ->options(SavingsTransaction::TYPES)->placeholder('Tous les types'),
                ])
                ->action(function (array $data) {
                    $url = route('reports.savings-movements', array_filter([
                        'company' => Filament::getTenant()?->id,
                        'from' => $data['from'],
                        'until' => $data['until'],
                        'type' => $data['type'] ?? null,
                    ]));
                    $this->js('window.open(' . json_encode($url) . ', "_blank")');
                }),
        ];
    }
}
