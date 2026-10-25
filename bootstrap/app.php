<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsurePasswordChange;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureSubdivisionAccess;
use App\Http\Middleware\PreventDuplicateCreation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

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
        // PHP drops the whole request above post_max_size, so no session or form data survives to redirect back with.
        // Browser forms fall through to resources/views/errors/413.blade.php.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                $message = 'The selected photos are too large to upload at once. Choose smaller or fewer photos, then try again.';

                return response()->json(['message' => $message, 'errors' => ['upload' => [$message]]], 413);
            }
        });
    })->create();
