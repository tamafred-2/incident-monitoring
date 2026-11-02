<?php

namespace App\Support;

use App\Enums\ActiveStatus;
use App\Models\GuardDevice;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Device registration and premises (geofence) checks for security guards.
 */
class GuardAccess
{
    public const DEVICE_COOKIE = 'guard_device';

    public const SESSION_LOCATION_KEY = 'guard_location_verified_at';

    /**
     * Five years, in minutes — lifetime of the device cookie.
     */
    private const DEVICE_COOKIE_MINUTES = 60 * 24 * 365 * 5;

    /**
     * Why a guard may not sign in from this request, or null when allowed.
     * Registers the device as pending the first time it is seen.
     */
    public static function loginDenial(Request $request, User $user): ?string
    {
        if (! $user->isSecurity()) {
            return null;
        }

        $deviceDenial = self::deviceDenial($request, $user, registerIfMissing: true);
        if ($deviceDenial) {
            return $deviceDenial;
        }

        return self::recordLocation($request, $user);
    }

    /**
     * Why this device may not be used by the guard, or null when approved.
     */
    public static function deviceDenial(Request $request, User $user, bool $registerIfMissing = false): ?string
    {
        if (! config('guard_access.require_device')) {
            return null;
        }

        $token = (string) $request->cookie(self::DEVICE_COOKIE, '');
        if (strlen($token) < 40) {
            if (! $registerIfMissing) {
                return 'This device is not registered. Please sign in again to request access.';
            }

            $token = Str::random(64);
            Cookie::queue(self::DEVICE_COOKIE, $token, self::DEVICE_COOKIE_MINUTES);
        }

        // A shared phone is approved separately for each guard who uses it.
        $hash = hash('sha256', $token);
        $device = GuardDevice::where('token_hash', $hash)->where('user_id', $user->user_id)->first();

        if (! $device) {
            if (! $registerIfMissing) {
                return 'This device is no longer registered. Please ask the admin to approve it again.';
            }

            GuardDevice::create([
                'user_id' => $user->user_id,
                'token_hash' => $hash,
                'device_name' => self::describeDevice((string) $request->userAgent()),
                'ip_address' => $request->ip(),
                'status' => GuardDevice::STATUS_PENDING,
            ]);

            return 'This device is not registered yet. A registration request was sent to the admin. Please sign in again once it is approved.';
        }

        if (! $device->isApproved()) {
            $device->update(['ip_address' => $request->ip()]);

            return 'This device is still waiting for admin approval. Please sign in again once it is approved.';
        }

        if (! $device->last_used_at || $device->last_used_at->lt(now()->subMinutes(5))) {
            $device->update(['last_used_at' => now(), 'ip_address' => $request->ip()]);
        }

        return null;
    }

    /**
     * Subdivision whose map pin defines the guard's premises, or null when the
     * premises check is not in effect.
     */
    public static function premisesFor(User $user): ?Subdivision
    {
        if (! config('guard_access.require_premises')) {
            return null;
        }

        $subdivision = $user->subdivision_id
            ? Subdivision::find($user->subdivision_id)
            : Subdivision::where('status', ActiveStatus::Active)->orderBy('subdivision_name')->first();

        if (! $subdivision || $subdivision->latitude === null || $subdivision->longitude === null) {
            return null;
        }

        return $subdivision;
    }

    public static function premisesCheckEnabled(): bool
    {
        return config('guard_access.require_premises')
            && Subdivision::whereNotNull('latitude')->whereNotNull('longitude')->exists();
    }

    /**
     * Check the location sent with the request and remember it in the session.
     * Returns why the guard is refused, or null when inside the premises.
     */
    public static function recordLocation(Request $request, User $user): ?string
    {
        $premises = self::premisesFor($user);
        if (! $premises) {
            return null;
        }

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        if (! is_numeric($latitude) || ! is_numeric($longitude)
            || abs((float) $latitude) > 90 || abs((float) $longitude) > 180) {
            $request->session()->forget(self::SESSION_LOCATION_KEY);

            return 'Your location is required. Please allow location access in your browser and try again.';
        }

        $radius = $premises->premises_radius_m ?: (int) config('guard_access.default_radius_meters');
        $accuracy = is_numeric($request->input('accuracy')) ? max(0, (float) $request->input('accuracy')) : 0;
        $allowance = min($accuracy, (float) config('guard_access.max_accuracy_allowance_meters'));
        $distance = self::distanceInMeters((float) $latitude, (float) $longitude, $premises->latitude, $premises->longitude);

        if ($distance > $radius + $allowance) {
            $request->session()->forget(self::SESSION_LOCATION_KEY);

            return 'You appear to be outside the subdivision premises. Guards can use the system only while on duty inside the premises.';
        }

        $request->session()->put(self::SESSION_LOCATION_KEY, now()->getTimestamp());

        return null;
    }

    /**
     * Whether the guard's last confirmed location is still recent enough.
     */
    public static function locationIsFresh(Request $request, User $user): bool
    {
        if (! self::premisesFor($user)) {
            return true;
        }

        $verifiedAt = (int) $request->session()->get(self::SESSION_LOCATION_KEY, 0);

        return $verifiedAt >= now()->subMinutes((int) config('guard_access.location_ttl_minutes'))->getTimestamp();
    }

    /**
     * Great-circle distance between two coordinates (haversine).
     */
    public static function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Short label such as "Chrome on Android" so the admin can recognise the device.
     */
    public static function describeDevice(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'unknown device',
        };

        return "{$browser} on {$os}";
    }
}
