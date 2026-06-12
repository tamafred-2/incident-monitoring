<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

/**
 * One-click "Continue as" login is backed by the encrypted `quick_login_id`
 * cookie. It is intentionally NOT cleared on logout so the account stays
 * available for fast re-login on a trusted device.
 */

class AuthenticatedSessionController extends Controller
{
    /**
     * Cookie that remembers which account can use one-click login.
     */
    private const QUICK_LOGIN_COOKIE = 'quick_login_id';

    /**
     * One year, in minutes — lifetime of the quick-login / remembered cookies.
     */
    private const REMEMBER_MINUTES = 60 * 24 * 365;

    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        return view('auth.login', [
            'quickLoginUser' => $this->resolveQuickLoginUser($request),
        ]);
    }

    /**
     * Log the remembered account in with a single click, no password required.
     */
    public function quickLogin(Request $request): RedirectResponse
    {
        $user = $this->resolveQuickLoginUser($request);

        if (! $user) {
            return redirect()->route('login')
                ->withCookie(Cookie::forget(self::QUICK_LOGIN_COOKIE));
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        // Refresh the cookie lifetime on each successful quick login.
        Cookie::queue(self::QUICK_LOGIN_COOKIE, (string) $user->getKey(), self::REMEMBER_MINUTES);

        if ($user->requires_password_change) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Please change your password before continuing.');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Forget the remembered account ("Use a different account").
     */
    public function forgetQuickLogin(): RedirectResponse
    {
        return redirect()->route('login')
            ->withCookie(Cookie::forget(self::QUICK_LOGIN_COOKIE))
            ->withCookie(Cookie::forget('remembered_email'));
    }

    /**
     * Resolve the account eligible for one-click login from the cookie, if any.
     */
    private function resolveQuickLoginUser(Request $request): ?User
    {
        $userId = $request->cookie(self::QUICK_LOGIN_COOKIE);

        if (! $userId) {
            return null;
        }

        return User::where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // When "remember me" is checked, remember the email (to pre-fill the
        // form) and enable one-click login for this account on this device.
        // Otherwise forget both so the next logout requires full credentials.
        if ($request->boolean('remember')) {
            Cookie::queue('remembered_email', $request->string('email')->toString(), self::REMEMBER_MINUTES);
            Cookie::queue(self::QUICK_LOGIN_COOKIE, (string) $request->user()->getKey(), self::REMEMBER_MINUTES);
        } else {
            Cookie::queue(Cookie::forget('remembered_email'));
            Cookie::queue(Cookie::forget(self::QUICK_LOGIN_COOKIE));
        }

        if ($request->user()?->requires_password_change) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Please change your password before continuing.');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
