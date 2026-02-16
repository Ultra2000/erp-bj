<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Compile invoice.blade.php
$compiled = \Illuminate\Support\Facades\Blade::compileString(
    file_get_contents('resources/views/sales/invoice.blade.php')
);
file_put_contents('storage/test_invoice_compiled.php', $compiled);
echo "Invoice HTML compiled OK\n";

// Compile invoice-pdf.blade.php
$compiled2 = \Illuminate\Support\Facades\Blade::compileString(
    file_get_contents('resources/views/sales/invoice-pdf.blade.php')
);
file_put_contents('storage/test_invoice_pdf_compiled.php', $compiled2);
echo "Invoice PDF compiled OK\n";

// Check syntax
exec('php -l storage/test_invoice_compiled.php 2>&1', $out1);
echo "Invoice HTML syntax: " . implode("\n", $out1) . "\n";

exec('php -l storage/test_invoice_pdf_compiled.php 2>&1', $out2);
echo "Invoice PDF syntax: " . implode("\n", $out2) . "\n";
