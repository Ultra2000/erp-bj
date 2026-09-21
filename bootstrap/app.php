<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\ModifyContentSecurityPolicy::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Session expirée (jeton CSRF invalide → 419 "Page Expired") :
        // au lieu de la page d'erreur brute, on redirige vers la connexion
        // du bon panneau avec un message clair.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            // Laravel convertit TokenMismatchException en HttpException(419)
            // avant d'appeler ce callback : on filtre donc sur le statut 419.
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            // Les requêtes Livewire/AJAX/JSON gèrent le 419 côté client.
            if ($request->expectsJson() || $request->header('X-Livewire')) {
                return null;
            }

            $path = $request->path();
            $loginPath = str_starts_with($path, 'caisse')
                ? '/caisse/login'
                : (str_starts_with($path, 'system')
                    ? '/system/login'
                    : '/admin/login');

            try {
                \Filament\Notifications\Notification::make()
                    ->title('Session expirée')
                    ->body('Votre session a expiré par inactivité. Veuillez vous reconnecter.')
                    ->warning()
                    ->send();
            } catch (\Throwable $ignored) {
                // Pas de session disponible : on redirige quand même.
            }

            return redirect()->guest(url($loginPath));
        });
    })->create();
