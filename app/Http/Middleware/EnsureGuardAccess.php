<?php

namespace App\Http\Middleware;

use App\Support\GuardAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs a guard out when their device is no longer approved or their
 * location has not been confirmed inside the premises recently.
 */
class EnsureGuardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSecurity() || $request->routeIs('logout', 'guard.location')) {
            return $next($request);
        }

        $denial = GuardAccess::deviceDenial($request, $user)
            ?? (GuardAccess::locationIsFresh($request, $user)
                ? null
                : 'We could not confirm that you are inside the subdivision premises. Please allow location access and sign in again.');

        if (! $denial) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => $denial, 'redirect' => route('login')], 401);
        }

        return redirect()->route('login')->with('error', $denial);
    }
}
