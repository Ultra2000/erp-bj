<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        // Nettoyer les champs de calcul temporaires (pas des colonnes DB)
        unset($data['items'], $data['_aib_amount_calc'], $data['_net_a_payer_calc']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $sale = $this->record;
        $items = $this->data['items'] ?? [];

        foreach ($items as $item) {
            $sale->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'vat_rate' => $item['vat_rate'] ?? null,
                'vat_category' => $item['vat_category'] ?? null,
                'tax_specific_amount' => $item['tax_specific_amount'] ?? null,
                'tax_specific_label' => $item['tax_specific_label'] ?? null,
                'is_wholesale' => $item['is_wholesale'] ?? false,
                'retail_unit_price' => $item['retail_unit_price'] ?? null,
                'total_price' => $item['total_price'],
            ]);
        }

        // Recharger la relation pour avoir tous les items frais
        $sale->refresh();
        $sale->calculateTotal();

        // Forcer les totaux corrects (sécurité contre observers qui pourraient écraser)
        $totalHt = $sale->items()->sum('total_price_ht');
        $totalVat = $sale->items()->sum('vat_amount');
        $discountPercent = floatval($sale->discount_percent ?? 0);
        $multiplier = 1 - ($discountPercent / 100);

        $finalTotalHt = round($totalHt * $multiplier, 2);
        $finalTotal = round(($totalHt + $totalVat) * $multiplier, 2);

        // Calculer l'AIB depuis les données du formulaire (source de vérité)
        $aibRate = $this->data['aib_rate'] ?? $sale->aib_rate ?? null;
        $aibAmount = 0;
        if ($aibRate) {
            $aibPercent = match ($aibRate) {
                'A' => 1,
                'B' => 5,
                default => 0,
            };
            $aibAmount = round($finalTotalHt * ($aibPercent / 100), 2);
        }

        \Log::info("CreateSale::afterCreate - aib_rate from form: " . ($this->data['aib_rate'] ?? 'null') . ", from model: " . ($sale->aib_rate ?? 'null') . ", aib_amount: {$aibAmount}");

        \DB::table('sales')->where('id', $sale->id)->update([
            'total_ht' => $finalTotalHt,
            'total_vat' => round($totalVat * $multiplier, 2),
            'total' => $finalTotal,
            'aib_rate' => $aibRate,
            'aib_amount' => $aibAmount,
        ]);
    }
}
