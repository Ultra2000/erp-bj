<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Supprimer')
                ->hidden(fn () => $this->record->status === 'completed'),
        ];
    }

    public function getTitle(): string
    {
        return 'Modifier la vente';
    }

    /**
     * Charger les lignes existantes dans le Repeater "items".
     * Ce Repeater n'est pas une relation Filament : sans cela, la section
     * Articles s'affiche vide à l'édition.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'vat_rate' => $item->vat_rate,
            'vat_category' => $item->vat_category,
            'tax_specific_amount' => $item->tax_specific_amount,
            'tax_specific_label' => $item->tax_specific_label,
            'is_wholesale' => $item->is_wholesale,
            'retail_unit_price' => $item->retail_unit_price,
            'total_price' => $item->total_price,
        ])->toArray();

        return $data;
    }

    /**
     * "items" n'est pas une colonne de la table sales : on le retire des
     * données enregistrées sur l'enregistrement (les lignes sont
     * synchronisées séparément dans afterSave).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['items'], $data['_aib_amount_calc'], $data['_net_a_payer_calc']);

        return $data;
    }

    /**
     * Synchroniser les lignes de vente depuis le Repeater, puis recalculer
     * les totaux. Verrouillé pour les ventes déjà terminées (formulaire
     * désactivé, articles non modifiables).
     */
    protected function afterSave(): void
    {
        $sale = $this->record;

        if ($sale->status === 'completed') {
            return;
        }

        $items = $this->data['items'] ?? [];
        $isExport = filter_var($this->data['is_export'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Empêcher calculateTotal de se déclencher N fois pendant la resynchronisation
        Sale::$skipRecalculationForIds[] = $sale->id;

        try {
            // Remplacer les lignes (suppression via query builder = pas d'événements
            // stock ; sans effet sur le stock pour une vente non terminée).
            $sale->items()->delete();

            foreach ($items as $item) {
                if (empty($item['product_id'])) {
                    continue;
                }
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

        // Un seul recalcul après toutes les lignes
        $sale->refresh();
        $sale->calculateTotal();

        // Réappliquer l'AIB depuis les données du formulaire
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
