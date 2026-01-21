<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Récupérer une vente récente
$sale = \App\Models\Sale::with('items.product')->orderBy('id', 'desc')->first();

if (!$sale) {
    echo "Aucune vente trouvée.\n";
    exit;
}

echo "=== Vente #{$sale->id} - {$sale->invoice_number} ===\n";
echo "Total HT stocké: {$sale->total_ht}\n";
echo "Total TVA stocké: {$sale->total_vat}\n";
echo "Total TTC stocké: {$sale->total}\n";
echo "\n";

echo "=== Détail des articles ===\n";
foreach ($sale->items as $item) {
    echo "- Produit: " . ($item->product->name ?? 'N/A') . "\n";
    echo "  - unit_price (HT): {$item->unit_price}\n";
    echo "  - unit_price_ht: {$item->unit_price_ht}\n";
    echo "  - vat_rate: {$item->vat_rate}\n";
    echo "  - vat_amount: {$item->vat_amount}\n";
    echo "  - total_price_ht: {$item->total_price_ht}\n";
    echo "  - total_price (TTC): {$item->total_price}\n";
    echo "  - quantity: {$item->quantity}\n";
    echo "  - vat_category: {$item->vat_category}\n";
    echo "\n";
}

// Vérifier le produit source
if ($sale->items->first()) {
    $product = $sale->items->first()->product;
    if ($product) {
        echo "=== Produit source ===\n";
        echo "- Nom: {$product->name}\n";
        echo "- vat_rate_sale: {$product->vat_rate_sale}\n";
        echo "- vat_category: {$product->vat_category}\n";
        echo "- price (vente HT): {$product->price}\n";
    }
}
