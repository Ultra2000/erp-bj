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
        // Laisser le modèle gérer la génération du numéro de facture
        // $data['invoice_number'] = 'FACT-' . strtoupper(Str::random(8));
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

        \DB::table('sales')->where('id', $sale->id)->update([
            'total_ht' => round($totalHt * $multiplier, 2),
            'total_vat' => round($totalVat * $multiplier, 2),
            'total' => round(($totalHt + $totalVat) * $multiplier, 2),
        ]);
    }
}
