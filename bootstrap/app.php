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
        // L'endpoint de tracking est appelé via navigator.sendBeacon (sans jeton CSRF).
        $middleware->validateCsrfTokens(except: [
            'track',
        ]);

        // Garde d'authentification du panneau admin.
        $middleware->alias([
            'auth.admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);

        // En-têtes de sécurité sur toutes les réponses web.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
