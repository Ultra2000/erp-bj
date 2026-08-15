<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Supprimer'),
        ];
    }

    public function getTitle(): string
    {
        return 'Modifier l\'achat';
    }

    protected function afterSave(): void
    {
        // Le Repeater a enregistré/ajouté/supprimé les lignes, mais les totaux
        // de l'achat ne sont pas recalculés automatiquement. On les recalcule
        // depuis les lignes puis on rafraîchit les champs affichés (Total HT,
        // TVA, Total TTC).
        $this->record->recalculateTotals();
        $this->refreshFormData(['total', 'total_ht', 'total_vat']);
    }
} 