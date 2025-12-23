<?php

use App\Http\Middleware\SetLocale;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ViewServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        AppServiceProvider::class,
        AuthServiceProvider::class,
        ViewServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class,
            // Other web middleware...
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
