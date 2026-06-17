<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetRequestController extends Controller
{
    /**
     * Show pending and recently completed password reset requests.
     */
    public function index(): View
    {
        // Invalidate requests that were never confirmed within the grace period.
        PasswordResetRequest::expireStale();

        $pending = PasswordResetRequest::with('user')
            ->where('status', PasswordResetRequest::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        $recent = PasswordResetRequest::with(['user', 'resolver'])
            ->whereIn('status', [PasswordResetRequest::STATUS_COMPLETED, PasswordResetRequest::STATUS_EXPIRED])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return view('password-resets.index', [
            'pendingRequests' => $pending,
            'recentRequests' => $recent,
        ]);
    }

    /**
     * Generate a new password for the requesting user and mark the request
     * completed. The plaintext is kept so the admin can relay it to the user.
     */
    public function resolve(Request $request, PasswordResetRequest $passwordResetRequest): RedirectResponse
    {
        // A request sitting past its grace period can no longer be resolved.
        if ($passwordResetRequest->isPending() && $passwordResetRequest->expires_at && $passwordResetRequest->expires_at->isPast()) {
            $passwordResetRequest->update(['status' => PasswordResetRequest::STATUS_EXPIRED]);
        }

        if ($passwordResetRequest->isExpired()) {
            return back()->with('error', 'This request expired because it was not confirmed in time. Ask the user to submit a new request.');
        }

        if (!$passwordResetRequest->isPending()) {
            return back()->with('error', 'This request has already been handled.');
        }

        $user = $passwordResetRequest->user;

        if (!$user) {
            $passwordResetRequest->delete();

            return back()->with('error', 'The account for this request no longer exists.');
        }

        $newPassword = Str::password(12);

        // Store only the hashed password. The plaintext is never persisted; it
        // is revealed to the admin exactly once via a flash payload below.
        $user->forceFill([
            'password' => $newPassword,
            'requires_password_change' => true,
            'temporary_password' => null,
        ])->save();

        $passwordResetRequest->update([
            'status' => PasswordResetRequest::STATUS_COMPLETED,
            'temporary_password' => null,
            'resolved_by' => $request->user()->user_id,
            'resolved_at' => now(),
        ]);

        return back()->with('reset_password_reveal', [
            'email' => $user->email,
            'password' => $newPassword,
        ]);
    }

    /**
     * Dismiss a request without resetting (e.g. handled manually or spam).
     */
    public function destroy(PasswordResetRequest $passwordResetRequest): RedirectResponse
    {
        $passwordResetRequest->delete();

        return back()->with('success', 'Password reset request dismissed.');
    }
}
