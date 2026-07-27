<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Inventory;
use App\Models\Quote;
use App\Models\StockMovement;
use App\Observers\AuditObserver;
use App\Observers\CompanyObserver;
use App\Observers\ActivityObserver;
use App\Observers\SaleObserver;
use App\Observers\PaymentObserver;
use App\Models\Payment;
use App\Policies\CustomerPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\RolePolicy;
use App\Policies\SalePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ==========================================
        // OPTIMISATIONS DE PERFORMANCE
        // ==========================================
        
        // Désactiver le lazy loading en développement pour détecter les problèmes N+1
        // Model::preventLazyLoading(!app()->isProduction());
        
        // Monitoring des requêtes lentes (> 100ms)
        if (config('app.debug')) {
            DB::listen(function ($query) {
                if ($query->time > 100) { // Plus de 100ms
                    Log::channel('slow-queries')->warning('Slow Query Detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time_ms' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }

        // ==========================================
        // POLYFILL CSS — classes Tailwind « arbitraires » non compilées
        // par le thème Filament (ex. max-h-[50vh], text-[10px], h-[calc(...)]).
        // Injecté dans le <head> de toutes les pages des panneaux Filament.
        // ==========================================
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => <<<'HTML'
<style>
.min-h-\[70vh\]{min-height:70vh}
.max-h-\[50vh\]{max-height:50vh}
.max-h-\[70vh\]{max-height:70vh}
.max-h-\[90vh\]{max-height:90vh}
.max-h-\[500px\]{max-height:500px}
.min-w-\[120px\]{min-width:120px}
.w-\[22px\]{width:22px}
.h-\[22px\]{height:22px}
.h-\[42px\]{height:42px}
.top-\[3px\]{top:3px}
.left-\[3px\]{left:3px}
.text-\[9px\]{font-size:9px}
.text-\[10px\]{font-size:10px}
.h-\[calc\(100vh-180px\)\]{height:calc(100vh - 180px)}
.hover\:scale-\[1\.02\]:hover{transform:scale(1.02)}
</style>
HTML
        );

        // ==========================================
        // OBSERVERS
        // ==========================================
        
        // Enregistrer les observers
        Company::observe(CompanyObserver::class);
        
        // Activity Log Observer pour company_id automatique
        \Spatie\Activitylog\Models\Activity::observe(ActivityObserver::class);
        
        // Audit Trail Observers
        Sale::observe(AuditObserver::class);
        Sale::observe(SaleObserver::class); // e-MCeF auto-certification
        Payment::observe(PaymentObserver::class);
        Purchase::observe(AuditObserver::class);
        Product::observe(AuditObserver::class);
        StockMovement::observe(AuditObserver::class);
        Quote::observe(AuditObserver::class);
        Inventory::observe(AuditObserver::class);

        // Observer pour invalider le cache du stock produit
        StockMovement::created(function ($movement) {
            Product::clearStockCacheForProducts([$movement->product_id]);
        });

        // ==========================================
        // POLICIES
        // ==========================================
        
        // Enregistrer les policies
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Quote::class, \App\Policies\QuotePolicy::class);
        Gate::policy(Purchase::class, PurchasePolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
    }
}
