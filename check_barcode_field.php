<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== STRUCTURE TABLE PRODUCTS ===" . PHP_EOL;

$columns = \Illuminate\Support\Facades\DB::select('PRAGMA table_info(products)');
foreach ($columns as $col) {
    echo $col->name . ' (' . $col->type . ')' . PHP_EOL;
}

echo PHP_EOL . "Recherche champ 'barcode' : ";
$hasBarcode = false;
foreach ($columns as $col) {
    if ($col->name === 'barcode') {
        $hasBarcode = true;
        break;
    }
}

echo $hasBarcode ? "✓ EXISTE" : "✗ N'EXISTE PAS - Il faut l'ajouter !";
echo PHP_EOL;