<?php

namespace App\Http\Controllers;

use App\Enums\ActiveStatus;
use App\Models\GuardDevice;
use App\Models\Subdivision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuardDeviceController extends Controller
{
    /**
     * Show device registration requests and approved guard devices.
     */
    public function index(): View
    {
        $pending = GuardDevice::with('user')
            ->where('status', GuardDevice::STATUS_PENDING)
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $approved = GuardDevice::with(['user', 'approver'])
            ->where('status', GuardDevice::STATUS_APPROVED)
            ->orderByDesc('approved_at')->orderByDesc('id')
            ->get();

        return view('guard-devices.index', [
            'pendingDevices' => $pending,
            'approvedDevices' => $approved,
            'subdivision' => $this->systemSubdivision(),
            'defaultRadius' => (int) config('guard_access.default_radius_meters'),
        ]);
    }

    /**
     * Set the map pin and radius that define the guards' premises.
     * Clearing the pin turns the premises check off.
     */
    public function updatePremises(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'premises_radius_m' => ['nullable', 'integer', 'min:50', 'max:5000'],
        ]);

        $subdivision = $this->systemSubdivision();
        abort_unless($subdivision !== null, 404);
        $subdivision->update($data);

        return back()->with('success', $subdivision->latitude === null
            ? 'Premises location cleared. Guards are no longer location-checked.'
            : 'Premises location saved.');
    }

    private function systemSubdivision(): ?Subdivision
    {
        return Subdivision::where('status', ActiveStatus::Active)->orderBy('subdivision_name')->first()
            ?? Subdivision::orderBy('subdivision_name')->first();
    }

    public function approve(Request $request, GuardDevice $guardDevice): RedirectResponse
    {
        if ($guardDevice->isApproved()) {
            return back()->with('error', 'This device is already approved.');
        }

        $guardDevice->update([
            'status' => GuardDevice::STATUS_APPROVED,
            'approved_by' => $request->user()->user_id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Device approved. The guard can now sign in from it.');
    }

    /**
     * Reject a request or remove an approved device. A removed device signs
     * the guard out on their next request.
     */
    public function destroy(GuardDevice $guardDevice): RedirectResponse
    {
        $wasApproved = $guardDevice->isApproved();
        $guardDevice->delete();

        return back()->with('success', $wasApproved ? 'Device removed. The guard can no longer use it.' : 'Device request rejected.');
    }
}
