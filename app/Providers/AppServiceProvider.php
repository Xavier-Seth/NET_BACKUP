<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

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
        Vite::prefetch(concurrency: 3);

        // Handle 403 Unauthorized errors and return an Inertia error page
        $this->app->bind(AuthorizationException::class, function () {
            return Response::inertia('Error', ['status' => 403])->setStatusCode(403);
        });
    }

    public const HOME = '/dashboard';
}
