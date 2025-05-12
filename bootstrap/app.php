<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Http\Middleware\HandleCors::class
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\CustomAuthenticate::class,
            'auth.tenant' => \App\Http\Middleware\AuthTenantRequest::class,
            'Pdf' => Barryvdh\DomPDF\Facade\Pdf::class,
            'auth.cookie.to.header' => \App\Http\Middleware\ConvertAuthCookieToHeader::class
        ]);

        $middleware->priority([
            \App\Http\Middleware\ConvertAuthCookieToHeader::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
