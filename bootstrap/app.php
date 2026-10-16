<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureLessonsCompleted;
use App\Http\Middleware\EnsureRequiredTwoFactorIsConfigured;
use App\Http\Middleware\PreventBrowserCaching;
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
        $middleware->web(append: [
            EnsureRequiredTwoFactorIsConfigured::class,
            PreventBrowserCaching::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'lessons.completed' => EnsureLessonsCompleted::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
        $middleware->trimStrings(except: ['source_code', 'starter_code']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
