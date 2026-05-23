<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Nouvelle vente';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['items'], $data['_aib_amount_calc'], $data['_net_a_payer_calc']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $sale = $this->record;
        $items = $this->data['items'] ?? [];
        $isExport = filter_var($this->data['is_export'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Empêcher calculateTotal de se déclencher N fois pendant la création des items
        Sale::$skipRecalculationForIds[] = $sale->id;

        try {
            foreach ($items as $item) {
                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $isExport ? 0 : ($item['vat_rate'] ?? null),
                    'vat_category' => $isExport ? 'C' : ($item['vat_category'] ?? null),
                    'tax_specific_amount' => $isExport ? null : ($item['tax_specific_amount'] ?? null),
                    'tax_specific_label' => $isExport ? null : ($item['tax_specific_label'] ?? null),
                    'is_wholesale' => $item['is_wholesale'] ?? false,
                    'retail_unit_price' => $item['retail_unit_price'] ?? null,
                    'total_price' => $item['total_price'],
                ]);
            }
        } finally {
            Sale::$skipRecalculationForIds = array_diff(
                Sale::$skipRecalculationForIds, [$sale->id]
            );
        }

        // Un seul calculateTotal après tous les items
        $sale->refresh();
        $sale->calculateTotal();

        // Appliquer l'AIB depuis les données du formulaire
        $aibRate = $this->data['aib_rate'] ?? $sale->aib_rate ?? null;
        if ($aibRate) {
            $aibPercent = match ($aibRate) {
                'A' => 1,
                'B' => 5,
                default => 0,
            };
            $sale->aib_rate = $aibRate;
            $sale->aib_amount = round($sale->total_ht * ($aibPercent / 100), 2);
            $sale->saveQuietly();
        }
    }
}
