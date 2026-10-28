<?php

namespace App\Filament\Resources\SaverResource\Pages;

use App\Filament\Concerns\HasTontineActions;
use App\Filament\Resources\SaverResource;
use App\Filament\Widgets\MyCollectionWidget;
use Filament\Resources\Pages\ListRecords;

class ListSavers extends ListRecords
{
    use HasTontineActions;

    protected static string $resource = SaverResource::class;

    public function getTitle(): string
    {
        return 'Épargnants';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MyCollectionWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->tontineDepositAction(),
            $this->tontineWithdrawAction(),
        ];
    }
}
