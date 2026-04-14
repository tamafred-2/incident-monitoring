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
            !$request->routeIs('profile.edit', 'profile.update', 'password.update', 'logout')
        ) {
            return redirect()
                ->route('profile.edit')
                ->with('warning', 'Please change your password before continuing.');
        }

        return $next($request);
    }
}
