<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Company;

// Find any sale with Group E items, or the latest sale
$sale = Sale::withoutGlobalScopes()
    ->whereHas('items', function($q) {
        $q->where('vat_category', 'E');
    })
    ->latest()
    ->first();

if (!$sale) {
    echo "No sale with Group E items found. Checking latest sale instead...\n";
    $sale = Sale::withoutGlobalScopes()->latest()->first();
}

if (!$sale) {
    echo "No sales found at all.\n";
    exit(1);
}

echo "Sale: #{$sale->id} - {$sale->invoice_number}\n";
$sale->load(['items.product', 'customer', 'warehouse', 'parent']);

echo "Items: " . $sale->items->count() . "\n";
foreach ($sale->items as $item) {
    echo "  - {$item->product->name}: vat_category={$item->vat_category}, vat_rate={$item->vat_rate}, tax_specific_amount={$item->tax_specific_amount}\n";
}

$company = $sale->company;
echo "Company: {$company->name}, emcef_enabled=" . ($company->emcef_enabled ? 'true' : 'false') . "\n";

// Try to render the view
try {
    $verificationUrl = 'https://test.example.com/verify';
    $verificationCode = 'testcode1234';
    
    $html = view('sales.invoice', [
        'sale' => $sale,
        'company' => $company,
        'verificationUrl' => $verificationUrl,
        'verificationCode' => $verificationCode,
        'previewMode' => true,
        'facturxXml' => null,
    ])->render();
    
    echo "\nHTML template rendered OK (" . strlen($html) . " bytes)\n";
} catch (Throwable $e) {
    echo "\nERROR rendering HTML template:\n";
    echo get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
