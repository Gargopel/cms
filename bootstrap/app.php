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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'core.admin.auth' => \App\Core\Auth\Http\Middleware\EnsureAdminAuthenticated::class,
            'core.install.installed' => \App\Core\Install\Http\Middleware\EnsureApplicationInstalled::class,
            'core.install.not-installed' => \App\Core\Install\Http\Middleware\EnsureApplicationNotInstalled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
