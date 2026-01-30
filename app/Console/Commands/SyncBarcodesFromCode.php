<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class SyncBarcodesFromCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync-barcodes {--force : Écraser les codes-barres existants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copie le code interne dans le champ barcode pour tous les produits';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $query = Product::query();
        
        if (!$force) {
            // Par défaut, ne copier que pour les produits sans code-barres
            $query->whereNull('barcode')->orWhere('barcode', '');
        }
        
        $products = $query->get();
        $updated = 0;
        
        $this->info("Traitement de {$products->count()} produits...");
        
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();
        
        foreach ($products as $product) {
            if ($product->code) {
                $product->barcode = $product->code;
                $product->save();
                $updated++;
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("✓ {$updated} produits mis à jour !");
        $this->info("Les codes internes ont été copiés dans le champ barcode.");
        
        return Command::SUCCESS;
    }
}
