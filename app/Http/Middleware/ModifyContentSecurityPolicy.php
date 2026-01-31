<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModifyContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Modifier la CSP pour autoriser unsafe-eval nécessaire pour Html5Qrcode et Alpine.js
        if ($response->headers->has('Content-Security-Policy')) {
            $csp = $response->headers->get('Content-Security-Policy');
            
            // Ajouter 'unsafe-eval' à script-src si pas déjà présent
            if (strpos($csp, "'unsafe-eval'") === false) {
                $csp = str_replace('script-src', "script-src 'unsafe-eval'", $csp);
                $response->headers->set('Content-Security-Policy', $csp);
            }
        }

        return $response;
    }
}
