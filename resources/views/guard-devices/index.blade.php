<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Guard Devices" subtitle="Security guards can sign in only from devices approved here." />
    </x-slot>

    <div class="page-section">
        <div class="page-shell flex flex-col gap-4 min-[1600px]:gap-6">
            @include('partials.alerts')

            @if ($subdivision)
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                         x-data="{
                             locating: false,
                             message: '',
                             useCurrentLocation() {
                                 if (!navigator.geolocation) { this.message = 'This browser cannot share its location.'; return; }
                                 this.locating = true;
                                 this.message = '';
                                 navigator.geolocation.getCurrentPosition((position) => {
                                     this.$refs.latitude.value = position.coords.latitude.toFixed(7);
                                     this.$refs.longitude.value = position.coords.longitude.toFixed(7);
                                     this.locating = false;
                                     this.message = 'Location filled in (accuracy about ' + Math.round(position.coords.accuracy) + ' m). Save to apply.';
                                 }, () => {
                                     this.locating = false;
                                     this.message = 'Could not get your location. Allow location access or enter the coordinates manually.';
                                 }, { enableHighAccuracy: true, timeout: 15000 });
                             },
                             clearPin() { this.$refs.latitude.value = ''; this.$refs.longitude.value = ''; }
                         }">
                    <div class="border-b border-slate-200 pb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Premises Location</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Guards can use the system only within this distance of the pin. Leave the pin empty to turn the location check off.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.guard-devices.premises') }}" class="mt-4 grid gap-4 md:grid-cols-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="premises-latitude" class="block text-sm font-medium text-slate-700">Latitude</label>
                            <input id="premises-latitude" x-ref="latitude" type="text" inputmode="decimal" name="latitude" value="{{ old('latitude', $subdivision->latitude) }}" placeholder="e.g. 14.6760000" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <x-input-error class="mt-1" :messages="$errors->get('latitude')" />
                        </div>
                        <div>
                            <label for="premises-longitude" class="block text-sm font-medium text-slate-700">Longitude</label>
                            <input id="premises-longitude" x-ref="longitude" type="text" inputmode="decimal" name="longitude" value="{{ old('longitude', $subdivision->longitude) }}" placeholder="e.g. 121.0437000" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <x-input-error class="mt-1" :messages="$errors->get('longitude')" />
                        </div>
                        <div>
                            <label for="premises-radius" class="block text-sm font-medium text-slate-700">Allowed radius (meters)</label>
                            <input id="premises-radius" type="number" min="50" max="5000" name="premises_radius_m" value="{{ old('premises_radius_m', $subdivision->premises_radius_m) }}" placeholder="{{ $defaultRadius }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <x-input-error class="mt-1" :messages="$errors->get('premises_radius_m')" />
                        </div>
                        <div class="md:col-span-3 flex flex-wrap items-center gap-3">
                            <button type="button" @click="useCurrentLocation()" :disabled="locating" class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-100 disabled:opacity-50">
                                <span x-show="!locating">Use my current location</span>
                                <span x-show="locating" x-cloak>Locating…</span>
                            </button>
                            <button type="button" @click="clearPin()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Clear pin</button>
                            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Save Premises</button>
                            <span class="text-sm {{ $subdivision->latitude !== null ? 'text-emerald-700' : 'text-slate-500' }}">
                                {{ $subdivision->latitude !== null ? 'Location check is on.' : 'Location check is off.' }}
                            </span>
                        </div>
                        <p x-show="message" x-cloak x-text="message" class="md:col-span-3 text-xs text-slate-500"></p>
                    </form>
                </section>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Registration Requests</h3>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                        {{ $pendingDevices->count() }} waiting
                    </span>
                </div>

                @forelse ($pendingDevices as $device)
                    <div class="flex flex-col gap-4 border-b border-slate-100 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900">{{ $device->user?->full_name ?? 'Unknown user' }}</p>
                            <p class="text-sm text-slate-500">{{ $device->device_name ?: 'Unknown device' }}@if ($device->ip_address) &middot; IP {{ $device->ip_address }}@endif</p>
                            <p class="mt-1 text-xs text-slate-400">Requested {{ $device->created_at->diffForHumans() }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('admin.guard-devices.approve', $device) }}">
                                @csrf
                                <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                                    Approve
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.guard-devices.destroy', $device) }}"
                                  onsubmit="return confirm('Reject this device request?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-400">No pending device requests.</p>
                @endforelse
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="border-b border-slate-200 pb-4 text-lg font-semibold text-slate-900">Approved Devices</h3>

                @forelse ($approvedDevices as $device)
                    <div class="flex flex-col gap-4 border-b border-slate-100 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900">{{ $device->user?->full_name ?? 'Unknown user' }}</p>
                            <p class="text-sm text-slate-500">{{ $device->device_name ?: 'Unknown device' }}@if ($device->ip_address) &middot; IP {{ $device->ip_address }}@endif</p>
                            <p class="mt-1 text-xs text-slate-400">
                                Approved {{ $device->approved_at?->diffForHumans() ?? '-' }}@if ($device->approver) by {{ $device->approver->full_name }}@endif
                                &middot; Last used {{ $device->last_used_at?->diffForHumans() ?? 'never' }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('admin.guard-devices.destroy', $device) }}"
                              onsubmit="return confirm('Remove this device? The guard will be signed out and can no longer use it.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-xl border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
                                Remove
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-400">No approved devices yet.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
