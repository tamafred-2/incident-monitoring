<x-app-layout>
    @php
        $defaultMonitoringTab = request()->query('tab');

        if (!in_array($defaultMonitoringTab, ['check-out', 'history'], true)) {
            $defaultMonitoringTab = 'check-out';
        }
    @endphp

    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <x-page-header title="Visitors" subtitle="Search a saved visitor profile, record a new visit, and monitor entry and checkout." />

            <div
                x-data="{ reminderOpen: false }"
                class="relative shrink-0"
                @mouseenter="reminderOpen = true"
                @mouseleave="reminderOpen = false"
            >
                <button
                    type="button"
                    @click="reminderOpen = !reminderOpen"
                    @focus="reminderOpen = true"
                    @blur="reminderOpen = false"
                    class="inline-flex items-center justify-center transition border rounded-full w-9 h-9 border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                    aria-label="Visitor approval reminder"
                >
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10A8 8 0 114 4.223a8 8 0 0114 5.777zM9 8a1 1 0 112 0v4a1 1 0 11-2 0V8zm1-3a1.25 1.25 0 100 2.5A1.25 1.25 0 0010 5z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div
                    x-cloak
                    x-show="reminderOpen"
                    x-transition.origin.top.right.duration.150ms
                    class="absolute right-0 z-30 mt-2 w-96 max-w-[calc(100vw-2rem)] rounded-2xl border border-brand-200 bg-white p-4 shadow-xl"
                >
                    <p class="text-sm font-semibold text-brand-800">Visitor Approval Reminder</p>
                    <p class="mt-2 text-sm leading-6">
                        For each resident visit, call the house owner using the registered phone number and record permission before allowing entry. Walk-ins do not require a resident or owner call.
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="page-section">
        <div
            x-data="{
                activeMonitoringTab: @js($defaultMonitoringTab),
                housesBySubdivision: {{ \Illuminate\Support\Js::from($housesBySubdivision) }},
                residentsByHouse: {{ \Illuminate\Support\Js::from($residentsByHouse) }},
                selectedSubdivision: '{{ $effectiveSubdivision }}',
                visitType: @js(old('visit_type', 'resident')),
                selectedHouseId: @js(old('visit_type', 'resident') === 'resident' ? (string) old('resident_house_id', $expectedVisit?->resident?->house_id ?? '') : ''),
                selectedHouseAddress: @js(old('visit_type', 'resident') === 'resident' ? old('house_address_or_unit', $expectedVisit?->resident?->house?->display_address ?? '') : ''),
                selectedStreet: '',
                selectedBlock: '',
                selectedLot: '',
                walkInLocation: @js(old('visit_type') === 'walk_in' ? old('house_address_or_unit', $expectedVisit?->resident?->house?->display_address ?? '') : ''),
                isVehicle: @js(old('on_vehicle') == '1' || old('plate_number') || old('passenger_count')),
                selectedResidentId: @js(old('resident_id', $expectedVisit?->resident_id ?? '')),
                hostSearch: @js(old('host_employee', $expectedVisit?->resident?->full_name ?? '')),
                selectedResidentPhone: '',
                selectedProfile: @js($selectedProfile),
                visitorSearch: @js($selectedProfile['name'] ?? ''),
                profileChoices: [],
                profileLookupBusy: false,
                profileLookupError: '',
                profileLookupVersion: 0,
                async lookupProfiles() {
                    const version = ++this.profileLookupVersion;
                    const term = this.visitorSearch;
                    this.profileLookupBusy = true;
                    this.profileLookupError = '';
                    const url = new URL(@js(route('visitor-profiles.index')), window.location.origin);
                    url.searchParams.set('q', term);
                    url.searchParams.set('subdivision_id', this.selectedSubdivision);
                    try {
                        const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                        if (!response.ok) throw new Error('Unable to search visitors. Please try again.');
                        const data = await response.json();
                        if (version === this.profileLookupVersion && term === this.visitorSearch) this.profileChoices = data.profiles;
                    } catch (error) {
                        if (version === this.profileLookupVersion) this.profileLookupError = error.message;
                    } finally {
                        if (version === this.profileLookupVersion) this.profileLookupBusy = false;
                    }
                },
                chooseProfile(profile) {
                    this.profileLookupVersion++;
                    this.profileLookupBusy = false;
                    this.selectedProfile = profile;
                    this.visitorSearch = profile.name;
                    this.profileChoices = [];
                    this.ownerPermission = '';
                    this.approvingOwnerId = '';
                },
                ownerPermission: '',
                approvingOwnerId: '',
                get requiresOwnerPermission() {
                    const host = this.subdivisionResidents.find(r => String(r.id) === String(this.selectedResidentId));
                    return this.visitType === 'resident' && Boolean(host);
                },
                get houseOwners() {
                    return (this.residentsByHouse[String(this.selectedHouseId)] || []).filter(r => r.is_owner);
                },
                hostOpen: false,
                init() {
                    this.initializeResidentSelection();
                },
                get availableHouses() {
                    return this.housesBySubdivision[this.selectedSubdivision] || [];
                },
                get subdivisionResidents() {
                    return this.availableHouses.flatMap(house => {
                        const residents = this.residentsByHouse[String(house.house_id)] || [];
                        return residents.map(resident => ({
                            ...resident,
                            house_id: String(house.house_id),
                            street: (house.street || '').trim(),
                            block: (house.block || '').trim(),
                            lot: (house.lot || '').trim(),
                            display_address: house.display_address || '',
                        }));
                    });
                },
                get availableResidents() {
                    const residents = this.subdivisionResidents;
                    if (!this.hostSearch.trim()) return residents.slice(0, 20);
                    const query = this.hostSearch.toLowerCase();
                    return residents.filter(r =>
                        r.name.toLowerCase().includes(query) ||
                        (r.phone || '').toLowerCase().includes(query) ||
                        (r.display_address || '').toLowerCase().includes(query)
                    ).slice(0, 20);
                },
                initializeResidentSelection() {
                    if (this.visitType !== 'resident') {
                        this.clearResidentSelection({ clearSearch: true });
                        return;
                    }

                    if (this.selectedResidentId) {
                        const matchedResident = this.subdivisionResidents.find(
                            resident => String(resident.id) === String(this.selectedResidentId)
                        );

                        if (matchedResident) {
                            this.applyResidentSelection(matchedResident);
                            return;
                        }
                    }

                    if (this.selectedHouseId) {
                        const matchedHouse = this.availableHouses.find(
                            h => String(h.house_id) === String(this.selectedHouseId)
                        );

                        if (matchedHouse) {
                            this.selectedStreet = (matchedHouse.street || '').trim();
                            this.selectedBlock = (matchedHouse.block || '').trim();
                            this.selectedLot = (matchedHouse.lot || '').trim();
                            this.selectedHouseId = String(matchedHouse.house_id);
                            this.selectedHouseAddress = matchedHouse.display_address || '';
                        }
                    }
                },
                clearResidentLocation() {
                    this.selectedStreet = '';
                    this.selectedBlock = '';
                    this.selectedLot = '';
                    this.selectedHouseId = '';
                    this.selectedHouseAddress = '';
                },
                clearResidentSelection({ clearSearch = false } = {}) {
                    this.ownerPermission = '';
                    this.approvingOwnerId = '';
                    this.selectedResidentId = '';
                    this.selectedResidentPhone = '';
                    if (clearSearch) {
                        this.hostSearch = '';
                    }
                    this.clearResidentLocation();
                },
                applyResidentSelection(resident) {
                    this.ownerPermission = '';
                    this.approvingOwnerId = '';
                    this.selectedResidentId = String(resident.id);
                    this.hostSearch = resident.name;
                    this.selectedResidentPhone = resident.phone || '';
                    this.selectedHouseId = resident.house_id || '';
                    this.selectedStreet = resident.street || '';
                    this.selectedBlock = resident.block || '';
                    this.selectedLot = resident.lot || '';
                    this.selectedHouseAddress = resident.display_address || '';
                },
                onResidentSearchInput() {
                    this.hostOpen = true;
                    this.clearResidentSelection();
                },
                selectResident(resident) {
                    this.applyResidentSelection(resident);
                    this.hostOpen = false;
                },
                setVisitType(type) {
                    this.visitType = type;
                    this.hostOpen = false;
                    if (type === 'walk_in') {
                        this.clearResidentSelection({ clearSearch: true });
                        return;
                    }

                    this.initializeResidentSelection();
                }
            }"
            class="page-shell flex flex-col gap-4 min-[1600px]:gap-6"
        >
            @include('partials.alerts')

            @if (auth()->user()->isSecurity())
                <x-modal name="visitor-check-in" :show="$errors->any() || (bool) $selectedProfile" maxWidth="6xl" contentOverflow="visible" focusable>
                    <div class="p-6 bg-white sm:p-8">
                        <div class="flex items-center justify-between gap-4 mb-5">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Add Visit</h3>
                                <p class="mt-1 text-sm text-slate-500">Use resident approval for home visits, or direct walk-in check-in for deliveries and amenity access.</p>
                            </div>
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="p-2 transition border rounded-xl border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700"
                                aria-label="Close visitor check-in modal"
                            >
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                    <form method="POST" action="{{ route('visitors.store') }}" enctype="multipart/form-data" class="space-y-6" data-visitor-check-in>
                        @csrf
                        @if ($expectedVisit)
                            <input type="hidden" name="expected_visit_id" value="{{ $expectedVisit->getKey() }}">
                            <p class="rounded-xl bg-brand-50 p-3 text-sm text-brand-800">Expected visit for {{ $selectedProfile['name'] ?? '' }} at {{ $expectedVisit->expected_at->format('M j, Y h:i A') }}. Complete this form only when the visitor arrives.</p>
                        @endif
                        <input type="hidden" name="_submission_token" value="{{ bin2hex(random_bytes(16)) }}">
                        <div data-check-in-errors role="alert" tabindex="-1" hidden class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"></div>
                        <input type="hidden" name="tab" value="check-in">
                        <input type="hidden" name="q" value="{{ $filterQ }}">
                        <input type="hidden" name="date_from" value="{{ $filterDateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $filterDateTo }}">
                        <input type="hidden" name="history_per_page" value="{{ $historyPerPage }}">
                        <input type="hidden" name="check_out_per_page" value="{{ $checkOutPerPage }}">

                        <input type="hidden" name="subdivision_id" value="{{ $effectiveSubdivision }}">
                        <input type="hidden" name="visit_type" :value="visitType">

                        <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visit Type</h4>
                            <div class="inline-flex p-1 mt-3 bg-white border rounded-xl border-slate-300">
                                <button
                                    type="button"
                                    x-on:click="setVisitType('resident')"
                                    :class="visitType === 'resident' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'"
                                    class="px-3 py-2 text-sm font-semibold transition rounded-lg"
                                >
                                    Resident Visit
                                </button>
                                <button
                                    type="button"
                                    x-on:click="setVisitType('walk_in')"
                                    :class="visitType === 'walk_in' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'"
                                    class="px-3 py-2 text-sm font-semibold transition rounded-lg"
                                >
                                    Walk-in
                                </button>
                            </div>
                        </div>

                        <div x-show="visitType === 'resident'" x-cloak class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Resident Visit Details</h4>
                                <p class="mt-1 text-sm text-slate-500">Select the resident first, then street, block, and lot will auto-fill for approval tracking.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <input type="hidden" name="house_address_or_unit" :value="visitType === 'resident' ? selectedHouseAddress : ''">
                                <input type="hidden" name="resident_house_id" :value="visitType === 'resident' ? selectedHouseId : ''">
                                <input type="hidden" name="resident_id" :value="visitType === 'resident' ? selectedResidentId : ''">

                                <div class="md:col-span-2 relative">
                                    <label class="block text-sm font-medium text-slate-700">Resident</label>
                                    <input
                                        type="text"
                                        x-model="hostSearch"
                                        x-on:input="onResidentSearchInput()"
                                        x-on:focus="hostOpen = subdivisionResidents.length > 0"
                                        x-on:click.away="hostOpen = false"
                                        :placeholder="'Select and search resident name...'"
                                        :disabled="visitType !== 'resident'"
                                        :required="visitType === 'resident'"
                                        autocomplete="off"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                    <p class="mt-2 text-xs text-slate-500">Select a resident from the suggestion list so the request reaches the correct registered phone number.</p>
                                    <ul
                                        x-cloak
                                        x-show="hostOpen && availableResidents.length > 0"
                                        class="absolute z-20 w-full mt-1 max-h-64 overflow-y-auto text-sm bg-white border shadow-lg rounded-xl border-slate-200"
                                    >
                                        <template x-for="resident in availableResidents" :key="resident.id + '-' + resident.house_id">
                                            <li
                                                x-on:mousedown.prevent="selectResident(resident)"
                                                class="cursor-pointer px-4 py-2.5 hover:bg-brand-50"
                                            >
                                                <p class="font-medium text-slate-800" x-text="resident.name"></p>
                                                <p class="text-xs text-slate-500" x-text="resident.display_address"></p>
                                                <p class="text-xs text-slate-500" x-text="resident.phone || 'No phone on file'"></p>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                <div x-cloak x-show="selectedResidentId" class="md:col-span-2 grid gap-4 md:grid-cols-4">
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Street</label>
                                        <input
                                            type="text"
                                            :value="selectedStreet"
                                            readonly
                                            :required="visitType === 'resident'"
                                            placeholder="Auto-filled from resident"
                                            class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 bg-slate-50 text-slate-700 focus:border-brand-500 focus:ring-brand-500"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Block</label>
                                        <input
                                            type="text"
                                            :value="selectedBlock"
                                            readonly
                                            :required="visitType === 'resident'"
                                            placeholder="Auto-filled from resident"
                                            class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 bg-slate-50 text-slate-700 focus:border-brand-500 focus:ring-brand-500"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Lot</label>
                                        <input
                                            type="text"
                                            :value="selectedLot"
                                            readonly
                                            :required="visitType === 'resident'"
                                            placeholder="Auto-filled from resident"
                                            class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 bg-slate-50 text-slate-700 focus:border-brand-500 focus:ring-brand-500"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Number</label>
                                        <input
                                            type="text"
                                            :value="selectedResidentPhone"
                                            readonly
                                            :required="visitType === 'resident'"
                                            placeholder="Auto-filled from resident"
                                            class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 bg-slate-50 text-slate-700 focus:border-brand-500 focus:ring-brand-500"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="requiresOwnerPermission" x-cloak class="p-5 bg-white border rounded-2xl border-slate-200">
                            <h4 class="font-semibold text-slate-900">Owner call status</h4>
                            <p class="mt-2 text-sm text-slate-600">Select the house owner and record whether they approved by phone or were unavailable, including when visiting the owner.</p>
                            <p x-show="houseOwners.length === 0" class="mt-2 text-sm text-rose-700">No active owner is registered for this house. Ask the administrator to update the household record before check-in.</p>
                            <template x-for="owner in houseOwners" :key="owner.id">
                                <label class="mt-3 flex items-center gap-3">
                                    <input type="radio" name="approving_owner_id" :value="owner.id" x-model="approvingOwnerId" @change="ownerPermission = ''" :disabled="!requiresOwnerPermission" :required="requiresOwnerPermission">
                                    <span x-text="owner.name"></span>
                                    <span x-show="owner.phone" class="font-semibold text-slate-700" x-text="owner.phone"></span>
                                    <span x-show="!owner.phone" class="text-sm text-slate-500">No phone recorded</span>
                                </label>
                            </template>
                            <label for="owner-call-status" class="mt-4 block text-sm font-medium text-slate-700">Owner call status</label>
                            <select id="owner-call-status" name="owner_permission" x-model="ownerPermission" :disabled="!requiresOwnerPermission || !approvingOwnerId" :required="requiresOwnerPermission" class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Select call status</option>
                                <option value="1">Called - owner approved entry</option>
                                <option value="0">Not called / owner unavailable</option>
                            </select>
                            <p x-show="ownerPermission === '0'" x-cloak class="mt-2 text-sm text-amber-700">You can check this visitor in. The visit will record that the owner was unavailable and approval was not obtained.</p>
                            @error('owner_permission') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                            @error('approving_owner_id') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="visitType === 'resident'" x-cloak class="p-5 border rounded-2xl border-brand-200 bg-brand-50/60">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-800">Approval Step</h4>
                            <p class="mt-2 text-sm leading-6 text-brand-700">
                                Record the owner call status before submitting. Submission checks the visitor in immediately, including when the owner is unavailable.
                            </p>
                        </div>

                        <div x-show="visitType === 'walk_in'" x-cloak class="p-5 border rounded-2xl border-emerald-200 bg-emerald-50/60">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-800">Direct Entry</h4>
                            <p class="mt-2 text-sm leading-6 text-emerald-700">
                                Use this for delivery riders, amenity users, and other visitors without a specific resident host.
                            </p>
                        </div>

                        <section class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Select Visitor</h4>
                            <label for="visit-profile-search" class="block text-sm text-slate-600">Search by name or phone</label>
                            <input id="visit-profile-search" type="search" x-model="visitorSearch" @input="selectedProfile = null; profileChoices = []; profileLookupVersion++" @input.debounce.250ms="lookupProfiles()" @focus="lookupProfiles()" class="w-full rounded-xl border-slate-300 text-sm" autocomplete="off">
                            <p x-show="profileLookupBusy" class="text-sm text-slate-500">Searching?</p>
                            <p x-show="profileLookupError" x-text="profileLookupError" role="alert" class="text-sm text-rose-700"></p>
                            <div x-show="profileChoices.length" class="max-h-52 overflow-y-auto rounded-xl border border-slate-200">
                                <template x-for="profile in profileChoices" :key="profile.id">
                                    <button type="button" @click="chooseProfile(profile)" class="block w-full border-b border-slate-100 px-4 py-3 text-left text-sm hover:bg-slate-50">
                                        <span class="font-semibold text-slate-900" x-text="profile.name"></span>
                                        <span class="ml-2 text-slate-500" x-text="(profile.phone || 'No phone') + (profile.is_inside ? ' - Currently inside' : '')"></span>
                                    </button>
                                </template>
                            </div>
                            <input type="hidden" name="visitor_profile_id" :value="selectedProfile?.id || ''">
                            <div x-show="selectedProfile" class="rounded-xl border border-brand-200 bg-brand-50 p-3 text-sm">
                                <p class="font-semibold text-brand-800" x-text="selectedProfile?.name"></p>
                                <p class="mt-1 text-slate-600" x-text="selectedProfile?.phone || 'No phone'"></p>
                                <p x-show="selectedProfile?.is_inside" class="mt-2 text-rose-700">This visitor is already inside. Check out their current visit first.</p>
                            </div>
                            <p class="text-sm text-slate-500">New visitor? <a href="{{ route('visitor-profiles.index', ['subdivision_id' => $effectiveSubdivision]) }}" class="font-semibold text-brand-700 underline">Add a visitor profile first</a>.</p>
                            <label for="visit-purpose" class="block text-sm font-medium text-slate-700">Purpose</label>
                            <textarea id="visit-purpose" name="purpose" rows="2" class="w-full rounded-xl border-slate-300 text-sm">{{ old('purpose', $expectedVisit?->purpose) }}</textarea>
                        </section>

                        <div x-show="selectedProfile && !selectedProfile.has_photo" x-cloak class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">ID Photo</h4>
                                <p class="mt-1 text-sm text-slate-500">This visitor has no saved ID photo. Upload one to complete their profile.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Upload ID Photo</label>
                                    <input
                                        type="file"
                                        name="id_photo"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        :required="selectedProfile && !selectedProfile.has_photo"
                                        :disabled="!selectedProfile || selectedProfile.has_photo"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Vehicle Option</h4>
                                <p class="mt-1 text-sm text-slate-500">Enable this when the visitor arrives by vehicle.</p>
                            </div>

                            <input type="hidden" name="on_vehicle" :value="isVehicle ? 1 : 0">

                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input type="checkbox" x-model="isVehicle" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                Visitor is on vehicle
                            </label>

                            <div x-show="isVehicle" x-cloak class="grid gap-4 mt-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Plate Number</label>
                                    <input
                                        type="text"
                                        name="plate_number"
                                        value="{{ old('plate_number') }}"
                                        :required="isVehicle"
                                        :disabled="!isVehicle"
                                        placeholder="e.g., ABC 1234"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Passenger Count</label>
                                    <input
                                        type="number"
                                        name="passenger_count"
                                        value="{{ old('passenger_count') }}"
                                        :required="isVehicle"
                                        :disabled="!isVehicle"
                                        min="1"
                                        max="20"
                                        step="1"
                                        placeholder="e.g., 2"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Vehicle Type</label>
                                    <input
                                        type="text"
                                        name="vehicle_type"
                                        value="{{ old('vehicle_type') }}"
                                        :required="isVehicle"
                                        :disabled="!isVehicle"
                                        placeholder="e.g., Sedan, SUV, Motorcycle"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Vehicle Color</label>
                                    <input
                                        type="text"
                                        name="vehicle_color"
                                        value="{{ old('vehicle_color') }}"
                                        :required="isVehicle"
                                        :disabled="!isVehicle"
                                        placeholder="e.g., White, Black, Blue"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                </div>
                            </div>
                        </div>

                        <div x-show="visitType === 'walk_in'" x-cloak class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4" x-data="{ durationChoice: @js((string) old('allowed_minutes', '')), durationUnit: @js(old('custom_duration_unit', 'minutes')) }">
                                <label for="walk-in-duration" class="block text-sm font-medium text-slate-700">Allowed visit duration</label>
                                <select id="walk-in-duration" name="allowed_minutes" x-model="durationChoice" :required="visitType === 'walk_in'" :disabled="visitType !== 'walk_in'" class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                                    <option value="">Select time limit</option>
                                    @foreach ([5, 10, 15, 30] as $minutes)
                                        <option value="{{ $minutes }}" @selected(old('allowed_minutes') == $minutes)>{{ $minutes }} minutes</option>
                                    @endforeach
                                    <option value="other">Other (custom duration)</option>
                                </select>
                                <div x-show="durationChoice === 'other'" x-cloak class="mt-3 grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="custom-duration" class="block text-sm font-medium text-slate-700">Duration</label>
                                        <input id="custom-duration" name="custom_duration" type="number" min="1" :max="durationUnit === 'hours' ? 12 : 720" step="1" value="{{ old('custom_duration') }}" :required="visitType === 'walk_in' && durationChoice === 'other'" :disabled="visitType !== 'walk_in' || durationChoice !== 'other'" class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                                        <x-input-error :messages="$errors->get('custom_duration')" />
                                    </div>
                                    <div>
                                        <label for="custom-duration-unit" class="block text-sm font-medium text-slate-700">Unit</label>
                                        <select id="custom-duration-unit" name="custom_duration_unit" x-model="durationUnit" :disabled="visitType !== 'walk_in' || durationChoice !== 'other'" class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                                            <option value="minutes">Minutes</option><option value="hours">Hours</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('custom_duration_unit')" />
                                    </div>
                                    <p class="col-span-2 text-xs text-slate-500">Enter a whole number, up to 12 hours (720 minutes).</p>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Starts at check-in, at any time of day. Visitors who exceed the limit are flagged as overdue.</p>
                                <x-input-error :messages="$errors->get('allowed_minutes')" />
                            </div>

                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Walk-in Details</h4>
                                <p class="mt-1 text-sm text-slate-500">Enter where the visitor is going inside the subdivision.</p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700">Destination / Location</label>
                                    <input
                                        type="text"
                                        name="house_address_or_unit"
                                        x-model="walkInLocation"
                                        :required="visitType === 'walk_in'"
                                        :disabled="visitType !== 'walk_in'"
                                        placeholder="e.g., Basketball Court, Gate Drop-off, Admin Office, Delivery"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-end gap-3">
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button :disabled="!selectedProfile || selectedProfile.is_inside" class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 shadow-sm transition hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="visitType === 'resident'" x-cloak>Check In Visitor</span>
                                <span x-show="visitType === 'walk_in'" x-cloak>Check In Visitor</span>
                            </button>
                        </div>
                    </form>
                    </div>
                </x-modal>
            @endif

            <div class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex flex-wrap items-end justify-between gap-3 px-2 pb-1 border-b border-slate-200">
                    <nav class="flex flex-wrap gap-6" aria-label="Visitor monitoring sections">
                        <a
                            href="#visitor-check-out"
                            @click.prevent="activeMonitoringTab = 'check-out'"
                            :class="activeMonitoringTab === 'check-out' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                            class="px-1 pb-3 text-sm font-semibold transition border-b-2"
                        >
                            Visitor Check-out
                        </a>
                        <a
                            href="#visitor-history"
                            @click.prevent="activeMonitoringTab = 'history'"
                            :class="activeMonitoringTab === 'history' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                            class="px-1 pb-3 text-sm font-semibold transition border-b-2"
                        >
                            Visitor History
                        </a>
                    </nav>
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <a href="{{ route('expected-visits.index') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Expected Visits</a>
                        @if (auth()->user()->hasRole('security'))
                            <a href="{{ route('reports.index', ['type' => 'visitors']) }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Reports</a>
                        @endif
                    </div>
                </div>
            </div>

            <div>
                <section
                    id="visitor-check-out"
                    x-cloak
                    x-show="activeMonitoringTab === 'check-out'"
                    x-transition.opacity.duration.150ms
                    class="min-w-0 scroll-mt-24"
                >
                    <div class="h-full overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Visitor Check-out</h3>
                                    <p class="mt-1 text-sm text-slate-500">Visitors currently inside the subdivision.</p>
                                </div>
                                <form method="GET" action="{{ route('visitors.index') }}" class="flex flex-wrap items-end gap-3">
                                @include('visitors.partials.chart-filters')
                                    <input type="hidden" name="tab" value="check-out">
                                    <input type="hidden" name="history_per_page" value="{{ $historyPerPage }}">
                                    <input type="hidden" name="check_out_per_page" value="{{ $checkOutPerPage }}">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Start</label>
                                        <input type="datetime-local" name="date_from" value="{{ $filterDateFrom ? \Illuminate\Support\Carbon::parse($filterDateFrom)->format('Y-m-d\TH:i') : '' }}" onchange="this.form.requestSubmit()" class="mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">End</label>
                                        <input type="datetime-local" name="date_to" value="{{ $filterDateTo ? \Illuminate\Support\Carbon::parse($filterDateTo)->format('Y-m-d\TH:i') : '' }}" onchange="this.form.requestSubmit()" class="mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Search</label>
                                        <input
                                            type="search"
                                            name="q"
                                            value="{{ $filterQ }}"
                                            placeholder="Name, phone, house"
                                            data-live-search="#visitor-inside-results,#visitor-history-results" autocomplete="off"
                                            class="mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500"
                                        >
                                    </div>
                                    @if (auth()->user()->isSecurity())
                                        <div class="flex items-end">
                                            <button
                                                type="button"
                                                x-on:click="$dispatch('open-modal', 'visitor-check-in')"
                                                class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800"
                                            >
                                                Add Visit
                                            </button>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </div>
                        <div id="visitor-inside-results"><x-scroll-list name="visitor-inside" :records="$insideVisitors">
                            <table class="min-w-full text-sm divide-y divide-slate-200">
                                <thead class="sticky top-0 z-10 bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Name</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Resident / Host</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Checked In</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody data-scroll-rows id="inside-visitors-rows" class="bg-white divide-y divide-slate-100">
@include('scroll-lists.visitor-inside')
</tbody>
                            </table>
                        </x-scroll-list></div>

                    </div>
                </section>

                <section
                    id="visitor-history"
                    x-cloak
                    x-show="activeMonitoringTab === 'history'"
                    x-transition.opacity.duration.150ms
                    class="min-w-0 scroll-mt-24"
                >
                    <div class="h-full overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                    <div class="flex flex-col gap-4 px-6 py-4 border-b border-slate-200 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ request()->boolean('chart_filter') ? 'Matching Visitor Check-ins' : 'Visitor History' }}</h3>
                            <p class="mt-1 text-sm text-slate-500">Browse visitor check-in/check-out records with host, purpose, and status history.</p>
                        </div>

                    </div>
                    <div id="visitor-history-results"><x-scroll-list name="visitor-history" :records="$visitors">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="sticky top-0 z-10 bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Name</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Resident / Host</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Check In</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Check Out</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Duration</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Action</th>
                                </tr>
                            </thead>
                            <tbody data-scroll-rows class="bg-white divide-y divide-slate-100">
@include('scroll-lists.visitor-history')
</tbody>
                        </table>
                    </x-scroll-list></div>

                    </div>
                </section>
            </div>


        </div>
    </div>

    @include('partials.live-search')
</x-app-layout>
