<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsurePasswordChange;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureSubdivisionAccess;
use App\Http\Middleware\PreventDuplicateCreation;
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
        $middleware->web(append: [EnsureActiveAccount::class, PreventDuplicateCreation::class]);
        $middleware->alias([
            'role' => EnsureRole::class,
            'subdivision' => EnsureSubdivisionAccess::class,
            'password.change' => EnsurePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
