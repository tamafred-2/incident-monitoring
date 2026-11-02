<?php

namespace App\Http\Controllers;

use App\Support\GuardAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuardLocationController extends Controller
{
    /**
     * Re-confirm that an on-duty guard is still inside the premises.
     */
    public function update(Request $request): JsonResponse
    {
        $denial = GuardAccess::deviceDenial($request, $request->user())
            ?? GuardAccess::recordLocation($request, $request->user());

        if (! $denial) {
            return response()->json(['ok' => true]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash('error', $denial);

        return response()->json(['message' => $denial, 'redirect' => route('login')], 401);
    }
}
