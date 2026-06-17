<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Max reset requests allowed per email+IP before the cooldown kicks in.
     */
    private const MAX_ATTEMPTS = 3;

    /**
     * Cooldown window in seconds once the limit is reached.
     */
    private const DECAY_SECONDS = 60;

    /**
     * Display the password reset request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Record a password reset request for an administrator to action. No
     * password is generated here and no email is sent; the administrator
     * resets the account and hands the new password to the user.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $throttleKey = $this->throttleKey($request);

        // Friendly cooldown instead of a raw 429 page: send back the seconds
        // remaining so the form can show a live countdown.
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return back()
                ->withInput($request->only('email'))
                ->with('retry_after', RateLimiter::availableIn($throttleKey));
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('We could not find an account with that email address.')]);
        }

        // Reuse an existing open request so a user can't pile up duplicates.
        // Each (re)submission restarts the grace period.
        PasswordResetRequest::updateOrCreate(
            [
                'user_id' => $user->user_id,
                'status' => PasswordResetRequest::STATUS_PENDING,
            ],
            [
                'email' => $user->email,
                'temporary_password' => null,
                'resolved_by' => null,
                'resolved_at' => null,
                'expires_at' => now()->addMinutes(PasswordResetRequest::GRACE_MINUTES),
            ]
        );

        // Send the user away from the form so they can't keep resubmitting.
        return redirect()->route('login')->with('status', __('Your password reset request has been sent. Please wait for the administrator to contact you to confirm that you requested this. Once confirmed, your password will be reset and a new one given to you. If you are not reached within :minutes minutes, the request will expire and you will need to submit a new one.', [
            'minutes' => PasswordResetRequest::GRACE_MINUTES,
        ]));
    }

    /**
     * Rate-limit key scoped to the submitted email and the requester's IP.
     */
    private function throttleKey(Request $request): string
    {
        return 'password-reset-request|' . Str::lower((string) $request->input('email')) . '|' . $request->ip();
    }
}
