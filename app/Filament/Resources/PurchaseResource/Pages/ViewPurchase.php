<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchase extends ViewRecord
{
    protected static string $resource = PurchaseResource::class;

    public function getTitle(): string
    {
        return 'Achat ' . $this->record->invoice_number;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Modifier'),
            Actions\Action::make('invoice')
                ->label('Facture PDF')
                ->icon('heroicon-o-document-text')
                ->url(fn () => route('purchases.invoice', $this->record))
                ->openUrlInNewTab()
                ->color('gray'),
        ];
    }
}
