<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubdivisionAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $candidate = $request->route('subdivision')
            ?? $request->route('incident')
            ?? $request->route('resident')
            ?? $request->route('visitor')
            ?? $request->route('gateVisitorLog')
            ?? $request->input('subdivision_id');

        $subdivisionId = null;

        if (is_object($candidate) && isset($candidate->subdivision_id)) {
            $subdivisionId = $candidate->subdivision_id;
        } elseif (is_scalar($candidate)) {
            $subdivisionId = $candidate;
        }

        if ($subdivisionId !== null && !$user->canAccessSubdivision($subdivisionId)) {
            return redirect()->route('dashboard')
                ->with('error', 'You cannot access that subdivision.');
        }

        return $next($request);
    }
}
