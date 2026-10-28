<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureNotSuspended;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('ajax')
                ->name('ajax.')
                ->group(base_path('routes/ajax.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class,
            EnsureNotSuspended::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'role' => EnsureRole::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
