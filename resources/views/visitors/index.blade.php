<x-app-layout>
    @php
        $defaultMonitoringTab = request()->query('tab');
        $visitorReportQuery = request()->query();
        $visitorExportPreviewUrl = route('visitors.print', $visitorReportQuery + ['preview' => 1, 'embed' => 1]);

        if (!in_array($defaultMonitoringTab, ['check-out', 'history'], true)) {
            $defaultMonitoringTab = 'check-out';
        }
    @endphp

    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <x-page-header title="Visitors" subtitle="Visitor approval follows the corrected flow: contact resident using the registered phone number or use the automated resident response request." />

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
                    class="inline-flex items-center justify-center transition border rounded-full w-9 h-9 border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
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
                    class="absolute right-0 z-30 mt-2 w-96 max-w-[calc(100vw-2rem)] rounded-2xl border border-sky-200 bg-white p-4 shadow-xl"
                >
                    <p class="text-sm font-semibold text-sky-800">Visitor Approval Reminder</p>
                    <p class="mt-2 text-sm leading-6">
                        For resident visits, Admin/Guard should contact the resident using the phone number registered in the system and record the resident response before allowing entry.
                        If automated approval is active, wait for the system response and apply the same allow or deny decision.
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div
            x-data="{
                activeMonitoringTab: @js($defaultMonitoringTab),
                housesBySubdivision: {{ \Illuminate\Support\Js::from($housesBySubdivision) }},
                residentsByHouse: {{ \Illuminate\Support\Js::from($residentsByHouse) }},
                selectedSubdivision: '{{ $effectiveSubdivision }}',
                visitType: @js(old('visit_type', 'resident')),
                selectedHouseId: @js(old('visit_type', 'resident') === 'resident' ? (string) old('resident_house_id', '') : ''),
                selectedHouseAddress: @js(old('visit_type', 'resident') === 'resident' ? old('house_address_or_unit', '') : ''),
                selectedStreet: '',
                selectedBlock: '',
                selectedLot: '',
                walkInLocation: @js(old('visit_type') === 'walk_in' ? old('house_address_or_unit', '') : ''),
                isVehicle: @js(old('on_vehicle') == '1' || old('plate_number') || old('passenger_count')),
                selectedResidentId: @js(old('resident_id', '')),
                hostSearch: @js(old('host_employee', '')),
                selectedResidentPhone: '',
                hostOpen: false,
                exportPreviewOpen: false,
                exportPreviewUrl: @js($visitorExportPreviewUrl),
                excelConfirmOpen: false,
                excelExportUrl: '',
                pdfConfirmOpen: false,
                pdfExportUrl: '',
                init() {
                    this.initializeResidentSelection();
                },
                openExportPreview() {
                    this.exportPreviewOpen = true;
                },
                closeExportPreview() {
                    this.exportPreviewOpen = false;
                },
                openExcelConfirm(url) {
                    this.excelExportUrl = url;
                    this.excelConfirmOpen = true;
                },
                closeExcelConfirm() {
                    this.excelConfirmOpen = false;
                    this.excelExportUrl = '';
                },
                proceedExcelExport() {
                    if (this.excelExportUrl) {
                        window.location.href = this.excelExportUrl;
                    }
                    this.closeExcelConfirm();
                },
                openPdfConfirm(url) {
                    this.pdfExportUrl = url;
                    this.pdfConfirmOpen = true;
                },
                closePdfConfirm() {
                    this.pdfConfirmOpen = false;
                    this.pdfExportUrl = '';
                },
                proceedPdfExport() {
                    if (this.pdfExportUrl) {
                        window.location.href = this.pdfExportUrl;
                    }
                    this.closePdfConfirm();
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
                    this.selectedResidentId = '';
                    this.selectedResidentPhone = '';
                    if (clearSearch) {
                        this.hostSearch = '';
                    }
                    this.clearResidentLocation();
                },
                applyResidentSelection(resident) {
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
            class="flex flex-col gap-6 px-4 mx-auto max-w-7xl sm:px-6 lg:px-8"
        >
            @include('partials.alerts')

            @if (auth()->user()->hasRole('security'))
                <x-modal name="visitor-check-in" :show="$errors->any()" maxWidth="6xl" contentOverflow="visible" focusable>
                    <div class="p-6 bg-white sm:p-8">
                        <div class="flex items-center justify-between gap-4 mb-5">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Register Visitor</h3>
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

                    <form method="POST" action="{{ route('visitors.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
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
                                    :class="visitType === 'resident' ? 'bg-sky-600 text-white' : 'text-slate-700 hover:bg-slate-100'"
                                    class="px-3 py-2 text-sm font-semibold transition rounded-lg"
                                >
                                    Resident Visit
                                </button>
                                <button
                                    type="button"
                                    x-on:click="setVisitType('walk_in')"
                                    :class="visitType === 'walk_in' ? 'bg-sky-600 text-white' : 'text-slate-700 hover:bg-slate-100'"
                                    class="px-3 py-2 text-sm font-semibold transition rounded-lg"
                                >
                                    Walk-in
                                </button>
                            </div>
                        </div>

                        <div x-show="visitType === 'resident'" x-cloak class="p-5 border rounded-2xl border-sky-200 bg-sky-50/60">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-800">Approval Step</h4>
                            <p class="mt-2 text-sm leading-6 text-sky-700">
                                Use this after confirming resident approval through registered contact. Submission will check the visitor in immediately.
                            </p>
                        </div>

                        <div x-show="visitType === 'walk_in'" x-cloak class="p-5 border rounded-2xl border-emerald-200 bg-emerald-50/60">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-800">Direct Entry</h4>
                            <p class="mt-2 text-sm leading-6 text-emerald-700">
                                Use this for delivery riders, amenity users, and other visitors without a specific resident host.
                            </p>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visitor Identity</h4>
                                <p class="mt-1 text-sm text-slate-500">Enter the visitor's name exactly as shown on their valid ID.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 whitespace-nowrap">Surname</label>
                                    <input type="text" name="surname" value="{{ old('surname') }}" required class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 whitespace-nowrap">First Name</label>
                                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 whitespace-nowrap">Middle Initials</label>
                                    <input type="text" name="middle_initials" value="{{ old('middle_initials') }}" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500" placeholder="M.I.">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 whitespace-nowrap">Extension</label>
                                    <input type="text" name="extension" value="{{ old('extension') }}" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500" placeholder="Jr.">
                                </div>
                            </div>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Contact</h4>
                                <p class="mt-1 text-sm text-slate-500">Visitor phone number is required for records and follow-up.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Phone</label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        value="{{ old('phone') }}"
                                        required
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="40"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                                    >
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700">Purpose</label>
                                    <textarea name="purpose" rows="2" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">{{ old('purpose') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">ID Photo</h4>
                                <p class="mt-1 text-sm text-slate-500">A valid ID photo is required before submitting.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Upload ID Photo</label>
                                    <input
                                        type="file"
                                        name="id_photo"
                                        accept="image/*"
                                        required
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
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
                                <input type="checkbox" x-model="isVehicle" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
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
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-400"
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
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-400"
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
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-400"
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
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                </div>
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
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-400"
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
                                                class="cursor-pointer px-4 py-2.5 hover:bg-sky-50"
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
                                            class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 bg-slate-50 text-slate-700 focus:border-sky-500 focus:ring-sky-500"
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
                                            class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 bg-slate-50 text-slate-700 focus:border-sky-500 focus:ring-sky-500"
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
                                            class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 bg-slate-50 text-slate-700 focus:border-sky-500 focus:ring-sky-500"
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
                                            class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 bg-slate-50 text-slate-700 focus:border-sky-500 focus:ring-sky-500"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="visitType === 'walk_in'" x-cloak class="p-5 bg-white border rounded-2xl border-slate-200">
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
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-400"
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
                            <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-sky-600 hover:bg-sky-700">
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
                            :class="activeMonitoringTab === 'check-out' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                            class="px-1 pb-3 text-sm font-semibold transition border-b-2"
                        >
                            Visitor Check-out
                        </a>
                        <a
                            href="#visitor-history"
                            @click.prevent="activeMonitoringTab = 'history'"
                            :class="activeMonitoringTab === 'history' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                            class="px-1 pb-3 text-sm font-semibold transition border-b-2"
                        >
                            Visitor History
                        </a>
                    </nav>
                    <button
                        type="button"
                        x-show="activeMonitoringTab === 'history'"
                        @click="openExportPreview()"
                        class="px-4 py-2 mb-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                    >
                        Export
                    </button>
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
                                    <input type="hidden" name="tab" value="check-out">
                                    <input type="hidden" name="history_per_page" value="{{ $historyPerPage }}">
                                    <input type="hidden" name="check_out_per_page" value="{{ $checkOutPerPage }}">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Start</label>
                                        <input type="datetime-local" name="date_from" value="{{ $filterDateFrom ? \Illuminate\Support\Carbon::parse($filterDateFrom)->format('Y-m-d\TH:i') : '' }}" onchange="this.form.requestSubmit()" class="mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">End</label>
                                        <input type="datetime-local" name="date_to" value="{{ $filterDateTo ? \Illuminate\Support\Carbon::parse($filterDateTo)->format('Y-m-d\TH:i') : '' }}" onchange="this.form.requestSubmit()" class="mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Search</label>
                                        <input
                                            type="search"
                                            name="q"
                                            value="{{ $filterQ }}"
                                            placeholder="Name, phone, house"
                                            data-live-filter data-filter-target="#inside-visitors-rows" autocomplete="off"
                                            class="mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                                        >
                                    </div>
                                    @if (auth()->user()->hasRole('security'))
                                        <div class="flex items-end">
                                            <button
                                                type="button"
                                                x-on:click="$dispatch('open-modal', 'visitor-check-in')"
                                                class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800"
                                            >
                                                Register Visitor
                                            </button>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Name</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Resident / Host</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Checked In</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="inside-visitors-rows" class="bg-white divide-y divide-slate-100">
                                    @forelse ($insideVisitors as $visitor)
                                        <tr data-row
                                            data-search="{{ \Illuminate\Support\Str::lower(trim($visitor->full_name.' '.$visitor->phone.' '.$visitor->purpose.' '.$visitor->host_employee.' '.$visitor->house_address_or_unit)) }}">
                                            <td class="px-6 py-4">
                                                <div class="min-w-[12rem]">
                                                    <div class="font-medium text-slate-900">{{ $visitor->full_name }}</div>
                                                    <div class="mt-1 text-xs text-slate-500">{{ $visitor->phone ?: 'No phone provided' }}</div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600">
                                                <div class="max-w-[14rem] truncate" title="{{ $visitor->host_employee ?: 'Walk-in' }}">
                                                    {{ $visitor->host_employee ?: 'Walk-in' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600">
                                                <div class="max-w-[12rem] truncate" title="{{ $visitor->house_address_or_unit ?: '-' }}">
                                                    {{ $visitor->house_address_or_unit ?: '-' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600">
                                                @if ($visitor->check_in)
                                                    <div class="min-w-[9rem]">
                                                        <div class="font-medium whitespace-nowrap text-slate-700">{{ $visitor->check_in->format('M j, Y') }}</div>
                                                        <div class="mt-1 text-xs whitespace-nowrap text-slate-500">{{ $visitor->check_in->format('h:i A') }}</div>
                                                    </div>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <a
                                                        href="{{ route('visitors.show', ['visitor' => $visitor->visitor_id]) }}"
                                                        class="px-3 py-2 text-xs font-semibold border rounded-lg border-slate-300 text-slate-700 hover:bg-slate-50"
                                                    >
                                                        View
                                                    </a>
                                                    @if (auth()->user()->hasRole('security'))
                                                        <form method="POST" action="{{ route('visitors.checkout', $visitor) }}">
                                                            @csrf
                                                            <input type="hidden" name="tab" value="check-out">
                                                            <input type="hidden" name="q" value="{{ $filterQ }}">
                                                            <input type="hidden" name="date_from" value="{{ $filterDateFrom }}">
                                                            <input type="hidden" name="date_to" value="{{ $filterDateTo }}">
                                                            <input type="hidden" name="history_per_page" value="{{ $historyPerPage }}">
                                                            <input type="hidden" name="check_out_per_page" value="{{ $checkOutPerPage }}">
                                                            <button class="px-3 py-2 text-xs font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">Check Out</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">No visitors are currently inside.</td>
                                        </tr>
                                    @endforelse
                                    <tr data-empty hidden>
                                        <td colspan="5" class="px-6 py-10 text-center text-slate-500">No inside visitors match your search. Press Enter to search all.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex flex-col gap-3 px-6 py-4 border-t border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-slate-500">
                                @if ($insideVisitors->total() > 0)
                                    Showing {{ $insideVisitors->firstItem() }}-{{ $insideVisitors->lastItem() }} of {{ $insideVisitors->total() }} visitors
                                @else
                                    No visitor records to paginate
                                @endif
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <form method="GET" action="{{ route('visitors.index') }}" class="flex items-center gap-2">
                                    @if ($filterQ !== '')
                                        <input type="hidden" name="q" value="{{ $filterQ }}">
                                    @endif
                                    @if ($filterSubdivision)
                                        <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                    @endif
                                    @if ($filterDateFrom)
                                        <input type="hidden" name="date_from" value="{{ $filterDateFrom }}">
                                    @endif
                                    @if ($filterDateTo)
                                        <input type="hidden" name="date_to" value="{{ $filterDateTo }}">
                                    @endif
                                    <input type="hidden" name="tab" value="check-out">
                                    <input type="hidden" name="history_per_page" value="{{ $historyPerPage }}">
                                    <label for="check-out-rows-per-page" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Rows</label>
                                    <input
                                        id="check-out-rows-per-page"
                                        type="text"
                                        name="check_out_per_page"
                                        list="check-out-row-size-options"
                                        value=""
                                        placeholder="{{ $checkOutPerPage }}"
                                        class="w-24 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                                        aria-label="Rows per page"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        pattern="[0-9]{1,3}"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3)"
                                        onchange="if (this.value.trim() !== '') { this.form.requestSubmit(); }"
                                        onkeydown="if (event.key === 'Enter') { event.preventDefault(); if (this.value.trim() !== '') { this.form.requestSubmit(); } }"
                                    >
                                    <datalist id="check-out-row-size-options">
                                        <option value="10"></option>
                                        <option value="25"></option>
                                        <option value="50"></option>
                                        <option value="100"></option>
                                    </datalist>
                                </form>
                                @if ($insideVisitors->onFirstPage())
                                    <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></span>
                                @else
                                    <a href="{{ $insideVisitors->previousPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></a>
                                @endif
                                <span class="px-3 py-2 text-sm font-semibold rounded-xl bg-slate-100 text-slate-700">
                                    Page {{ $insideVisitors->currentPage() }} of {{ max($insideVisitors->lastPage(), 1) }}
                                </span>
                                @if ($insideVisitors->hasMorePages())
                                    <a href="{{ $insideVisitors->nextPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></a>
                                @else
                                    <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></span>
                                @endif
                            </div>
                        </div>
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
                            <h3 class="text-lg font-semibold text-slate-900">Visitor History</h3>
                            <p class="mt-1 text-sm text-slate-500">Browse visitor check-in/check-out records with host, purpose, and status history.</p>
                        </div>

                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Name</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Resident / Host</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Check In</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Check Out</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @forelse ($visitors as $visitor)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="min-w-[12rem]">
                                                <div class="font-medium text-slate-900">{{ $visitor->full_name }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $visitor->phone ?: 'No phone provided' }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <div class="max-w-[14rem] truncate" title="{{ $visitor->host_employee ?: 'Walk-in' }}">
                                                {{ $visitor->host_employee ?: 'Walk-in' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <div class="max-w-[12rem] truncate" title="{{ $visitor->house_address_or_unit ?: '-' }}">
                                                {{ $visitor->house_address_or_unit ?: '-' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            @if ($visitor->check_in)
                                                <div class="min-w-[9rem]">
                                                    <div class="font-medium whitespace-nowrap text-slate-700">{{ $visitor->check_in->format('M j, Y') }}</div>
                                                    <div class="mt-1 text-xs whitespace-nowrap text-slate-500">{{ $visitor->check_in->format('h:i A') }}</div>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            @if ($visitor->check_out)
                                                <div class="min-w-[9rem]">
                                                    <div class="font-medium whitespace-nowrap text-slate-700">{{ $visitor->check_out->format('M j, Y') }}</div>
                                                    <div class="mt-1 text-xs whitespace-nowrap text-slate-500">{{ $visitor->check_out->format('h:i A') }}</div>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <div class="flex items-center gap-2">
                                                <a
                                                    href="{{ route('visitors.show', ['visitor' => $visitor->visitor_id]) }}"
                                                    class="px-3 py-2 text-xs font-semibold border rounded-lg border-slate-300 text-slate-700 hover:bg-slate-50"
                                                >
                                                    View
                                                </a>
                                                @if (auth()->user()->isAdmin())
                                                    <a
                                                        href="{{ route('visitors.edit', array_merge(['visitor' => $visitor->visitor_id], request()->only(['tab', 'q', 'subdivision_id', 'history_per_page', 'check_out_per_page']))) }}"
                                                        class="px-3 py-2 text-xs font-semibold border rounded-lg border-sky-300 text-sky-700 hover:bg-sky-50"
                                                    >
                                                        Edit
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-slate-500">No visitors found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col gap-3 px-6 py-4 border-t border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-500">
                            @if ($visitors->total() > 0)
                                Showing {{ $visitors->firstItem() }}-{{ $visitors->lastItem() }} of {{ $visitors->total() }} visitor records
                            @else
                                No visitor records to paginate
                            @endif
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="GET" action="{{ route('visitors.index') }}" class="flex items-center gap-2">
                                @if ($filterQ !== '')
                                    <input type="hidden" name="q" value="{{ $filterQ }}">
                                @endif
                                @if ($filterSubdivision)
                                    <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                @endif
                                @if ($filterDateFrom)
                                    <input type="hidden" name="date_from" value="{{ $filterDateFrom }}">
                                @endif
                                @if ($filterDateTo)
                                    <input type="hidden" name="date_to" value="{{ $filterDateTo }}">
                                @endif
                                <input type="hidden" name="tab" value="history">
                                <input type="hidden" name="check_out_per_page" value="{{ $checkOutPerPage }}">
                                <label for="history-rows-per-page" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Rows</label>
                                <input
                                    id="history-rows-per-page"
                                    type="text"
                                    name="history_per_page"
                                    list="history-row-size-options"
                                    value=""
                                    placeholder="{{ $historyPerPage }}"
                                    class="w-24 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                                    aria-label="Rows per page"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    pattern="[0-9]{1,3}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3)"
                                    onchange="if (this.value.trim() !== '') { this.form.requestSubmit(); }"
                                    onkeydown="if (event.key === 'Enter') { event.preventDefault(); if (this.value.trim() !== '') { this.form.requestSubmit(); } }"
                                >
                                <datalist id="history-row-size-options">
                                    <option value="10"></option>
                                    <option value="25"></option>
                                    <option value="50"></option>
                                    <option value="100"></option>
                                </datalist>
                            </form>
                            @if ($visitors->onFirstPage())
                                <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></span>
                            @else
                                <a href="{{ $visitors->previousPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></a>
                            @endif
                            <span class="px-3 py-2 text-sm font-semibold rounded-xl bg-slate-100 text-slate-700">
                                Page {{ $visitors->currentPage() }} of {{ max($visitors->lastPage(), 1) }}
                            </span>
                            @if ($visitors->hasMorePages())
                                <a href="{{ $visitors->nextPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></a>
                            @else
                                <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></span>
                            @endif
                        </div>
                    </div>
                    </div>
                </section>
            </div>


            <div
                x-cloak
                x-show="exportPreviewOpen"
                x-on:keydown.escape.window="closeExportPreview()"
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-slate-950/80"
                style="display: none;"
            >
                <div class="absolute inset-0" @click="closeExportPreview()"></div>
                <div class="relative w-full max-w-6xl overflow-hidden bg-white shadow-2xl rounded-2xl">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-200">
                        <h3 class="text-base font-semibold text-slate-900">Visitor Report Preview</h3>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="openExcelConfirm('{{ route('visitors.export', $visitorReportQuery + ['format' => 'excel']) }}')"
                                class="px-3 py-2 text-xs font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700"
                            >
                                To Excel
                            </button>
                            <button
                                type="button"
                                @click="openPdfConfirm('{{ route('visitors.export', $visitorReportQuery + ['format' => 'pdf']) }}')"
                                class="px-3 py-2 text-xs font-semibold text-white rounded-lg bg-rose-600 hover:bg-rose-700"
                            >
                                To PDF
                            </button>
                            <a
                                href="{{ route('visitors.print', $visitorReportQuery + ['autoprint' => 1]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="px-3 py-2 text-xs font-semibold border rounded-lg border-slate-300 text-slate-700 hover:bg-slate-50"
                            >
                                Print
                            </a>
                            <button
                                type="button"
                                @click="closeExportPreview()"
                                class="px-3 py-2 text-xs font-semibold border rounded-lg border-slate-300 text-slate-700 hover:bg-slate-50"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                    <div class="h-[70vh] bg-slate-100">
                        <iframe :src="exportPreviewUrl" class="w-full h-full border-0" title="Visitor report preview"></iframe>
                    </div>
                </div>
            </div>

            <div
                x-cloak
                x-show="excelConfirmOpen"
                x-on:keydown.escape.window="closeExcelConfirm()"
                class="fixed inset-0 z-[60] flex items-center justify-center px-4 py-6 bg-slate-950/80"
                style="display: none;"
            >
                <div class="absolute inset-0" @click="closeExcelConfirm()"></div>
                <div class="relative w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-2xl ring-1 ring-emerald-200">
                    <div class="px-5 py-4 border-b border-emerald-100 bg-emerald-50">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Export</p>
                        <h3 class="mt-1 text-base font-semibold text-slate-900">Confirm Excel Export</h3>
                        <p class="mt-1 text-sm text-slate-700">Generate the visitor history report now as an Excel-compatible CSV file.</p>
                    </div>
                    <div class="flex items-center justify-end gap-2 px-5 py-4 bg-slate-50">
                        <button
                            type="button"
                            @click="closeExcelConfirm()"
                            class="px-3 py-2 text-sm font-semibold border rounded-lg border-slate-300 text-slate-700 hover:bg-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="proceedExcelExport()"
                            class="px-3 py-2 text-sm font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700"
                        >
                            Continue
                        </button>
                    </div>
                </div>
            </div>

            <div
                x-cloak
                x-show="pdfConfirmOpen"
                x-on:keydown.escape.window="closePdfConfirm()"
                class="fixed inset-0 z-[60] flex items-center justify-center px-4 py-6 bg-slate-950/80"
                style="display: none;"
            >
                <div class="absolute inset-0" @click="closePdfConfirm()"></div>
                <div class="relative w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-2xl">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h3 class="text-base font-semibold text-slate-900">Confirm PDF Export</h3>
                        <p class="mt-1 text-sm text-slate-600">Generate and download the visitor history report as a PDF file?</p>
                    </div>
                    <div class="flex items-center justify-end gap-2 px-5 py-4 bg-slate-50">
                        <button
                            type="button"
                            @click="closePdfConfirm()"
                            class="px-3 py-2 text-sm font-semibold border rounded-lg border-slate-300 text-slate-700 hover:bg-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="proceedPdfExport()"
                            class="px-3 py-2 text-sm font-semibold text-white rounded-lg bg-rose-600 hover:bg-rose-700"
                        >
                            Continue
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('partials.live-search')
</x-app-layout>
