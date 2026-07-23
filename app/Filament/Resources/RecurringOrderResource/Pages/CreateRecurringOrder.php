<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateRecurringOrder extends CreateRecord
{
    protected static string $resource = RecurringOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()->id;
        $data['user_id'] = auth()->id();

        // Total initial (recalculé ensuite depuis les lignes)
        $items = $data['items'] ?? [];
        $data['total'] = collect($items)->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
