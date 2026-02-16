<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

try {
    $blade = app('blade.compiler');
    $html = $blade->compileString(file_get_contents('resources/views/sales/invoice.blade.php'));
    echo "HTML template: OK\n";
} catch (Throwable $e) {
    echo "HTML template ERROR: " . $e->getMessage() . "\n";
}

try {
    $blade = app('blade.compiler');
    $pdf = $blade->compileString(file_get_contents('resources/views/sales/invoice-pdf.blade.php'));
    echo "PDF template: OK\n";
} catch (Throwable $e) {
    echo "PDF template ERROR: " . $e->getMessage() . "\n";
}
