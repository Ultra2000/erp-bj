<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecurringOrder extends EditRecord
{
    protected static string $resource = RecurringOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('execute')
                ->label('Exécuter maintenant')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $sale = $this->record->generateSale();
                    if ($sale) {
                        \Filament\Notifications\Notification::make()
                            ->title('Vente générée')
                            ->body("Vente #{$sale->invoice_number} créée")
                            ->success()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status === 'active'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Total (recalculé ensuite depuis les lignes via le modèle)
        $items = $data['items'] ?? [];
        $data['total'] = collect($items)->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));

        return $data;
    }
}
