<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global Middleware for Web Routes
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Register Route Middleware (Aliases)
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class, // Auth Middleware
            'admin' => \App\Http\Middleware\AdminMiddleware::class, // Custom Admin Middleware
            'role' => \App\Http\Middleware\RoleMiddleware::class, // Role-Based Middleware
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

