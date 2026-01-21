<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\LowStockAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stock:check-low 
                            {--company= : ID de l\'entreprise (optionnel, toutes par défaut)}
                            {--notify-email : Envoyer des notifications par email}
                            {--dry-run : Afficher sans notifier}';

    /**
     * The console command description.
     */
    protected $description = 'Vérifie les produits en stock bas et envoie des alertes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company');
        $notifyEmail = $this->option('notify-email');
        $dryRun = $this->option('dry-run');

        $companies = $companyId 
            ? Company::where('id', $companyId)->get()
            : Company::all();

        $totalAlerts = 0;

        foreach ($companies as $company) {
            $this->info("📦 Vérification pour: {$company->name}");

            // Récupérer les produits en stock bas par entrepôt
            $lowStockProducts = DB::table('product_warehouse')
                ->join('products', 'products.id', '=', 'product_warehouse.product_id')
                ->join('warehouses', 'warehouses.id', '=', 'product_warehouse.warehouse_id')
                ->where('products.company_id', $company->id)
                ->whereRaw('product_warehouse.quantity <= products.min_stock')
                ->where('products.min_stock', '>', 0)
                ->select([
                    'products.id',
                    'products.name',
                    'products.code',
                    'products.min_stock',
                    'product_warehouse.quantity as stock',
                    'warehouses.id as warehouse_id',
                    'warehouses.name as warehouse_name',
                ])
                ->get();

            if ($lowStockProducts->isEmpty()) {
                $this->line("  ✅ Aucun produit en stock bas");
                continue;
            }

            // Grouper par entrepôt
            $byWarehouse = $lowStockProducts->groupBy('warehouse_id');

            foreach ($byWarehouse as $warehouseId => $products) {
                $warehouseName = $products->first()->warehouse_name;
                $count = $products->count();
                
                $this->warn("  ⚠️ {$warehouseName}: {$count} produit(s) en stock bas");

                if ($dryRun) {
                    foreach ($products as $product) {
                        $this->line("    - {$product->name} ({$product->code}): {$product->stock}/{$product->min_stock}");
                    }
                    continue;
                }

                // Préparer les données pour la notification
                $productData = $products->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code,
                    'stock' => $p->stock,
                    'min_stock' => $p->min_stock,
                ])->toArray();

                // Récupérer les utilisateurs à notifier (admins de l'entreprise)
                $admins = User::where('company_id', $company->id)
                    ->where(function ($q) {
                        $q->where('role', 'admin')
                          ->orWhere('is_super_admin', true);
                    })
                    ->get();

                // Utilisateurs assignés à cet entrepôt
                $warehouseUsers = User::where('company_id', $company->id)
                    ->whereHas('warehouses', fn ($q) => $q->where('warehouses.id', $warehouseId))
                    ->get();

                $usersToNotify = $admins->merge($warehouseUsers)->unique('id');

                foreach ($usersToNotify as $user) {
                    // Notification Filament (dans l'app)
                    FilamentNotification::make()
                        ->title('⚠️ Stock Bas - ' . $warehouseName)
                        ->body("{$count} produit(s) ont atteint leur seuil minimum")
                        ->warning()
                        ->icon('heroicon-o-exclamation-triangle')
                        ->actions([
                            Action::make('view')
                                ->label('Voir les produits')
                                ->url("/admin/{$company->slug}/products?tableFilters[low_stock][value]=1")
                                ->button(),
                        ])
                        ->sendToDatabase($user);

                    // Notification Email (si activé)
                    if ($notifyEmail) {
                        try {
                            $user->notify(new LowStockAlert($productData, $warehouseName));
                            $this->line("    📧 Email envoyé à: {$user->email}");
                        } catch (\Exception $e) {
                            Log::error("Erreur envoi email stock bas", [
                                'user' => $user->email,
                                'error' => $e->getMessage(),
                            ]);
                            $this->error("    ❌ Erreur email: {$user->email}");
                        }
                    }
                }

                $totalAlerts += $count;
            }
        }

        $this->newLine();
        $this->info("🏁 Terminé. {$totalAlerts} alerte(s) générée(s).");

        return Command::SUCCESS;
    }
}
