<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $requiresPasswordChange = (bool) $request->user()->requires_password_change;

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => [$requiresPasswordChange ? 'nullable' : 'required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'requires_password_change' => false,
            'temporary_password' => null,
        ]);

        // After a forced change there's nothing else on the locked screen, so
        // send the user into the app instead of back to the password form.
        if ($requiresPasswordChange) {
            return redirect()->route('dashboard')->with('status', 'Your password has been updated.');
        }

        return back()->with('status', 'password-updated');
    }
}
