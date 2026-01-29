<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ÉTAT DU STOCK AVANT VENTE POS ===" . PHP_EOL;

$product = \App\Models\Product::first();
if ($product) {
    echo 'Produit: ' . $product->name . PHP_EOL;
    echo 'Stock global: ' . $product->stock . PHP_EOL;
    echo 'ID: ' . $product->id . PHP_EOL;
    
    $stocks = \DB::table('product_warehouse')->where('product_id', $product->id)->get();
    echo 'Stocks par entrepôt:' . PHP_EOL;
    foreach ($stocks as $stock) {
        $warehouse = \App\Models\Warehouse::find($stock->warehouse_id);
        echo '  - Entrepôt ' . ($warehouse ? $warehouse->name : 'Unknown') . ': ' . $stock->quantity . PHP_EOL;
    }
} else {
    echo 'Aucun produit trouvé' . PHP_EOL;
}

echo PHP_EOL . "Notez ces valeurs, puis faites une vente POS avec ce produit." . PHP_EOL;