<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user &&
            $user->requires_password_change &&
            !$request->routeIs('password.force-change', 'password.update', 'logout')
        ) {
            return redirect()
                ->route('password.force-change')
                ->with('warning', 'Please change your password before continuing.');
        }

        return $next($request);
    }
}
