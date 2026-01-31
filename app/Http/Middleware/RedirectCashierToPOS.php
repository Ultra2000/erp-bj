<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectCashierToPOS
{
    /**
     * Redirige les utilisateurs avec restriction entrepôt (caissiers) vers la page POS
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Si l'utilisateur est un caissier (a une restriction entrepôt)
        if ($user && $user->hasWarehouseRestriction()) {
            // Si on essaie d'accéder au dashboard, rediriger vers le POS
            $path = $request->path();
            
            // Vérifier si c'est une page dashboard ou la racine du panel admin
            if (preg_match('#^admin/[^/]+/?$#', $path) || str_ends_with($path, '/dashboard')) {
                $tenant = \Filament\Facades\Filament::getTenant();
                if ($tenant) {
                    return redirect()->route('filament.admin.pages.cash-register-page', ['tenant' => $tenant->slug]);
                }
            }
        }
        
        return $next($request);
    }
}
