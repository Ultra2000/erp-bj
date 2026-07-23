<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    public function getTitle(): string
    {
        return 'Nouvel achat';
    }

    protected function afterCreate(): void
    {
        // Recalculer les totaux depuis les lignes.
        // La réception de stock (mouvement + stock entrepôt) est gérée
        // automatiquement par l'événement PurchaseItem::created via
        // Warehouse::adjustStock lorsque l'achat est "completed".
        $this->record->recalculateTotals();
    }
} 