<?php

use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function () {
            $central = config('tenancy.central_domain');

            // Platform: marketing, registration, Super Admin.
            Route::middleware('web')
                ->domain($central)
                ->group(base_path('routes/web.php'));

            // Charities: {subdomain}.amanah.app
            Route::middleware(['web', ResolveTenant::class])
                ->domain('{subdomain}.'.$central)
                ->group(base_path('routes/tenant.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
