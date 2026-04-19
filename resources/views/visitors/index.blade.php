<x-app-layout>
    @php
        $defaultMonitoringTab = request()->query('tab');

        if (!in_array($defaultMonitoringTab, ['pending', 'check-out', 'history'], true)) {
            $defaultMonitoringTab = 'check-out';
        }
    @endphp

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Visitors</h2>
            <p class="mt-1 text-sm text-slate-500">Visitor monitoring now keeps check-out and history visible together, with check-in available as a quick action.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div
            x-data="{
                activeMonitoringTab: @js($defaultMonitoringTab),
                housesBySubdivision: {{ \Illuminate\Support\Js::from($housesBySubdivision) }},
                residentsByHouse: {{ \Illuminate\Support\Js::from($residentsByHouse) }},
                selectedSubdivision: '{{ $effectiveSubdivision }}',
                selectedHouse: @js(old('house_address_or_unit', '')),
                hostEmployee: @js(old('host_employee', '')),
                hostSearch: @js(old('host_employee', '')),
                hostResidentId: @js(old('host_resident_id', '')),
                idPhotoPreview: null,
                idPhotoName: null,
                hostOpen: false,
                visitType: @js(old('visit_type', 'resident')),
                get availableHouses() {
                    return this.housesBySubdivision[this.selectedSubdivision] || [];
                },
                get availableResidents() {
                    const residents = this.residentsByHouse[this.selectedHouse] || [];
                    if (!this.hostSearch.trim()) return residents;
                    return residents.filter(r => r.name.toLowerCase().includes(this.hostSearch.toLowerCase()));
                },
                selectResident(resident) {
                    this.hostEmployee = resident.name;
                    this.hostSearch = resident.name;
                    this.hostResidentId = resident.id;
                    this.hostOpen = false;
                },
                onHouseChange() {
                    this.hostEmployee = '';
                    this.hostSearch = '';
                    this.hostResidentId = '';
                }
            }"
            class="flex flex-col gap-6 px-4 mx-auto max-w-7xl sm:px-6 lg:px-8"
        >
            @include('partials.alerts')

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <form method="GET" action="{{ route('visitors.index') }}" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="tab" :value="activeMonitoringTab">
                    <input type="hidden" name="view" value="{{ $historyView }}">
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-slate-700">Search</label>
                        <input
                            type="search"
                            name="q"
                            value="{{ $filterQ }}"
                            placeholder="Name, host, company, house, status"
                            class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-sky-600 hover:bg-sky-700">Apply</button>
                        <a
                            href="{{ route('visitors.index', ['view' => $historyView]) }}"
                            class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                        >
                            Clear
                        </a>
                        @if (auth()->user()->hasRole('security'))
                            <button
                                type="button"
                                x-on:click="$dispatch('open-modal', 'visitor-check-in')"
                                class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800"
                            >
                                Check In Visitor
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            @if (auth()->user()->hasRole('security'))
                <x-modal name="visitor-check-in" :show="$errors->any()" maxWidth="4xl" focusable>
                    <div class="p-6 bg-white sm:p-8">
                        <div class="flex items-center justify-between gap-4 mb-5">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Visitor Check-in</h3>
                                <p class="mt-1 text-sm text-slate-500">Log a new visitor using grouped sections for identity, contact, and visit details.</p>
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
                        <input type="hidden" name="view" value="{{ $historyView }}">

                        <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visit Type</h4>
                                <p class="mt-1 text-sm text-slate-500">Is the visitor here for a specific resident, or for a general purpose (delivery, amenity use, etc.)?</p>
                            </div>
                            <input type="hidden" name="subdivision_id" value="{{ $effectiveSubdivision }}">
                            <div class="flex flex-wrap gap-3">
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-3 text-sm font-medium transition"
                                    :class="visitType === 'resident' ? 'border-sky-500 bg-sky-50 text-sky-700' : 'border-slate-300 text-slate-700 hover:bg-slate-50'">
                                    <input type="radio" name="visit_type" value="resident" x-model="visitType" class="border-slate-300 text-sky-600 focus:ring-sky-500">
                                    Visiting a Resident
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-3 text-sm font-medium transition"
                                    :class="visitType === 'general' ? 'border-sky-500 bg-sky-50 text-sky-700' : 'border-slate-300 text-slate-700 hover:bg-slate-50'">
                                    <input type="radio" name="visit_type" value="general" x-model="visitType" class="border-slate-300 text-sky-600 focus:ring-sky-500">
                                    General / Walk-in
                                </label>
                            </div>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visitor Identity</h4>
                                <p class="mt-1 text-sm text-slate-500">Enter the visitor’s name exactly as shown on their valid ID.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1.2fr_1.2fr_0.7fr_0.7fr]">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Surname</label>
                                    <input type="text" name="surname" value="{{ old('surname') }}" required class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">First Name</label>
                                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Middle Initials</label>
                                    <input type="text" name="middle_initials" value="{{ old('middle_initials') }}" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500" placeholder="M.I.">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Extension</label>
                                    <input type="text" name="extension" value="{{ old('extension') }}" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500" placeholder="Jr.">
                                </div>
                            </div>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Contact And ID</h4>
                                <p class="mt-1 text-sm text-slate-500">Required contact and identification details.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Phone</label>
                                    <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">ID Photo</label>
                                    <div class="mt-1" x-data="{
                                        photoMenuOpen: false,
                                        cameraOpen: false,
                                        stream: null,
                                        async startCamera() {
                                            this.photoMenuOpen = false;
                                            this.cameraOpen = true;
                                            await $nextTick();
                                            try {
                                                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                                                $refs.cameraVideo.srcObject = this.stream;
                                            } catch(e) {
                                                alert('Camera access denied or not available.');
                                                this.cameraOpen = false;
                                            }
                                        },
                                        stopCamera() {
                                            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
                                            this.cameraOpen = false;
                                        },
                                        capturePhoto() {
                                            const canvas = $refs.cameraCanvas;
                                            const video = $refs.cameraVideo;
                                            canvas.width = video.videoWidth;
                                            canvas.height = video.videoHeight;
                                            canvas.getContext('2d').drawImage(video, 0, 0);
                                            const self = this;
                                            canvas.toBlob(blob => {
                                                const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
                                                const dt = new DataTransfer();
                                                dt.items.add(file);
                                                $refs.uploadInput.files = dt.files;
                                                $refs.uploadInput.dispatchEvent(new Event('change'));
                                                self.cameraOpen = false;
                                                if (self.stream) { self.stream.getTracks().forEach(t => t.stop()); self.stream = null; }
                                            }, 'image/jpeg', 0.92);
                                        }
                                    }" @click.away="photoMenuOpen = false">

                                        <div class="flex items-start gap-4">
                                            <div class="relative">
                                                <button
                                                    type="button"
                                                    @click="photoMenuOpen = !photoMenuOpen"
                                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border rounded-xl border-slate-300 bg-slate-50 text-slate-700 hover:bg-slate-100"
                                                >
                                                    <svg class="w-4 h-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                                                    <span>Upload / Take Photo</span>
                                                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.23 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                                                </button>

                                                <div
                                                    x-show="photoMenuOpen"
                                                    x-transition
                                                    class="absolute left-0 z-20 mt-2 overflow-hidden bg-white border shadow-lg w-52 rounded-xl border-slate-200"
                                                >
                                                    <button
                                                        type="button"
                                                        @click="$refs.uploadInput.click(); photoMenuOpen = false"
                                                        class="w-full px-4 py-2.5 text-sm text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2"
                                                    >
                                                        <svg class="w-4 h-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                                        Upload from device
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="startCamera()"
                                                        class="w-full px-4 py-2.5 text-sm text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2"
                                                    >
                                                        <svg class="w-4 h-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.121-1.121A2 2 0 0011.172 3H8.828a2 2 0 00-1.414.586L6.293 4.707A1 1 0 015.586 5H4zm6 9a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                                                        Take photo
                                                    </button>
                                                </div>
                                            </div>

                                            <input
                                                x-ref="uploadInput"
                                                type="file"
                                                name="id_photo"
                                                accept="image/*"
                                                required
                                                class="sr-only"
                                                @change="const f = $event.target.files[0]; if(f){ idPhotoPreview = URL.createObjectURL(f); idPhotoName = f.name; }"
                                            >

                                            <div x-show="idPhotoPreview" class="flex flex-col gap-1">
                                                <img :src="idPhotoPreview" class="object-cover w-24 h-16 border rounded-lg border-slate-200" alt="ID preview">
                                            </div>
                                        </div>

                                        <p class="mt-1.5 text-xs text-slate-500" x-text="idPhotoName || 'No file chosen'"></p>

                                        {{-- Inline camera viewer --}}
                                        <div x-show="cameraOpen" x-cloak class="mt-3 overflow-hidden border rounded-2xl border-slate-200">
                                            <video x-ref="cameraVideo" autoplay playsinline muted class="w-full max-h-64 bg-black object-cover"></video>
                                            <canvas x-ref="cameraCanvas" class="hidden"></canvas>
                                            <div class="flex items-center justify-between gap-3 px-4 py-3 bg-slate-50">
                                                <button
                                                    type="button"
                                                    @click="stopCamera()"
                                                    class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-100"
                                                >Cancel</button>
                                                <button
                                                    type="button"
                                                    @click="capturePhoto()"
                                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl bg-sky-600 hover:bg-sky-700"
                                                >
                                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.121-1.121A2 2 0 0011.172 3H8.828a2 2 0 00-1.414.586L6.293 4.707A1 1 0 015.586 5H4zm6 9a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                                                    Capture
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200" x-show="visitType === 'resident'" x-cloak>
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visit Details</h4>
                                <p class="mt-1 text-sm text-slate-500">Who they are visiting and the reason for entry.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">House / Unit</label>
                                    <select name="house_address_or_unit" x-model="selectedHouse" x-on:change="onHouseChange()" :required="visitType === 'resident'" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                        <option value="">Select house / unit</option>
                                        <template x-for="house in availableHouses" :key="house">
                                            <option :value="house" x-text="house"></option>
                                        </template>
                                    </select>
                                </div>
                                <div x-data class="relative">
                                    <label class="block text-sm font-medium text-slate-700">Resident</label>
                                    <input type="hidden" name="host_employee" :value="hostEmployee">
                                    <input type="hidden" name="host_resident_id" :value="hostResidentId">
                                    <input
                                        type="text"
                                        x-model="hostSearch"
                                        x-on:input="hostOpen = true; hostEmployee = hostSearch; hostResidentId = ''"
                                        x-on:focus="hostOpen = availableResidents.length > 0"
                                        x-on:click.away="hostOpen = false"
                                        :placeholder="selectedHouse ? 'Type to search residents...' : 'Select a house first'"
                                        :disabled="!selectedHouse"
                                        :required="visitType === 'resident'"
                                        autocomplete="off"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                    <ul
                                        x-cloak
                                        x-show="hostOpen && availableResidents.length > 0"
                                        class="absolute z-20 w-full mt-1 overflow-hidden text-sm bg-white border shadow-lg rounded-xl border-slate-200"
                                    >
                                        <template x-for="resident in availableResidents" :key="resident.id">
                                            <li
                                                x-text="resident.name"
                                                x-on:mousedown.prevent="selectResident(resident)"
                                                class="cursor-pointer px-4 py-2.5 hover:bg-sky-50 hover:text-sky-700"
                                            ></li>
                                        </template>
                                    </ul>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700">
                                        Resident Code
                                        <span class="font-normal text-slate-400">(optional — skip to send approval request)</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="resident_code"
                                        value="{{ old('resident_code') }}"
                                        placeholder="e.g. E7FC90"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                                    >
                                    <p class="mt-1 text-xs text-slate-500">If provided and valid, the visitor will be checked in immediately. Otherwise, an approval request is sent to the resident.</p>
                                    <x-input-error :messages="$errors->get('resident_code')" class="mt-1" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700">Purpose</label>
                                    <textarea name="purpose" rows="2" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">{{ old('purpose') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200" x-show="visitType === 'general'" x-cloak>
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visit Details</h4>
                                <p class="mt-1 text-sm text-slate-500">Describe the reason for entry (e.g. delivery, repair, amenity use).</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Purpose</label>
                                <textarea name="purpose" rows="3" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">{{ old('purpose') }}</textarea>
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
                            <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-sky-600 hover:bg-sky-700">Check In Visitor</button>
                        </div>
                    </form>
                    </div>
                </x-modal>
            @endif

            <div class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200">
                <nav class="flex flex-wrap gap-6 px-2 pb-1 border-b border-slate-200" aria-label="Visitor monitoring sections">
                    <a
                        href="#visitor-pending"
                        @click.prevent="activeMonitoringTab = 'pending'"
                        :class="activeMonitoringTab === 'pending' ? 'border-amber-500 text-amber-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-1 pb-3 text-sm font-semibold transition border-b-2 flex items-center gap-2"
                    >
                        Pending Requests
                        @if ($pendingRequests->isNotEmpty())
                            <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white rounded-full bg-amber-500">{{ $pendingRequests->count() }}</span>
                        @endif
                    </a>
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
            </div>

            <div>
                <section
                    id="visitor-pending"
                    x-cloak
                    x-show="activeMonitoringTab === 'pending'"
                    x-transition.opacity.duration.150ms
                    class="min-w-0 scroll-mt-24"
                >
                    <div class="h-full overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Pending Visitor Requests</h3>
                            <p class="mt-1 text-sm text-slate-500">Visitors waiting for resident approval before being checked in.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">ID Photo</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Visitor</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Phone</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Resident</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Purpose</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Requested</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    @forelse ($pendingRequests as $req)
                                        <tr>
                                            <td class="px-6 py-4">
                                                @if ($req->id_photo_path)
                                                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'pending-id-photo-{{ $req->request_id }}')">
                                                        <img src="{{ route('visitors.id-photo', $req->request_id) }}" alt="ID" class="object-cover w-10 h-10 rounded-lg border border-slate-200 hover:opacity-80">
                                                    </button>
                                                @else
                                                    <span class="text-xs text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 font-medium text-slate-900">{{ $req->visitor_name }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ $req->phone ?: '-' }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ $req->house_address_or_unit ?: '-' }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ $req->resident->full_name ?? '-' }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ \Illuminate\Support\Str::limit($req->purpose ?: '-', 40) }}</td>
                                            <td class="px-6 py-4 text-slate-500">{{ $req->requested_at->format('M j, Y H:i') }}</td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Pending</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-6 py-10 text-center text-slate-500">No pending visitor requests.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section
                    id="visitor-check-out"
                    x-cloak
                    x-show="activeMonitoringTab === 'check-out'"
                    x-transition.opacity.duration.150ms
                    class="min-w-0 scroll-mt-24"
                >
                    <div class="h-full overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h3 class="text-lg font-semibold text-slate-900">Visitor Check-out</h3>
                            <p class="mt-1 text-sm text-slate-500">Visitors who are still inside and ready to be checked out.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Name</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Company</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Host</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Checked In</th>
                                        @if (auth()->user()->hasRole('security'))
                                            <th class="px-6 py-3 font-semibold text-left text-slate-600">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    @forelse ($insideVisitors as $visitor)
                                        <tr>
                                            <td class="px-6 py-4 font-medium text-slate-900">{{ $visitor->full_name }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ $visitor->company ?: '-' }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ $visitor->host_employee ?: '-' }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ $visitor->house_address_or_unit ?: '-' }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ optional($visitor->check_in)->format('M j, Y H:i') }}</td>
                                            @if (auth()->user()->hasRole('security'))
                                                <td class="px-6 py-4">
                                                    <form method="POST" action="{{ route('visitors.checkout', $visitor) }}">
                                                        @csrf
                                                        <input type="hidden" name="tab" value="check-out">
                                                        <button class="px-3 py-2 text-xs font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">Check Out</button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ auth()->user()->hasRole('security') ? 6 : 5 }}" class="px-6 py-10 text-center text-slate-500">No visitors are currently inside.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
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
                    <div class="flex flex-col gap-4 px-6 py-4 border-b border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Visitor History</h3>
                            <p class="mt-1 text-sm text-slate-500">Browse previous visitor records, statuses, and timestamps.</p>
                        </div>

                        <form method="GET" action="{{ route('visitors.index') }}" class="flex flex-wrap items-end gap-3">
                            @if ($filterQ !== '')
                                <input type="hidden" name="q" value="{{ $filterQ }}">
                            @endif
                            @if ($filterSubdivision)
                                <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                            @endif
                            <input type="hidden" name="tab" value="history">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">View</label>
                                <select
                                    name="view"
                                    class="mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                                >
                                    <option value="active" @selected($historyView === 'active')>Active</option>
                                    <option value="deleted" @selected($historyView === 'deleted')>Deleted</option>
                                    <option value="all" @selected($historyView === 'all')>All</option>
                                </select>
                            </div>

                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white transition rounded-xl bg-sky-600 hover:bg-sky-700"
                            >
                                Apply
                            </button>

                            <a
                                href="{{ route('visitors.index', array_filter([
                                    'tab' => 'history',
                                    'q' => $filterQ,
                                    'subdivision_id' => $filterSubdivision,
                                ], fn ($value) => filled($value))) }}"
                                class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                            >
                                Clear
                            </a>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Name</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Phone</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Company</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Purpose</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Host</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Check In</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Check Out</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Status</th>
                                    @if ($historyView !== 'active')
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Archived At</th>
                                    @endif
                                    @if (auth()->user()->hasRole('security'))
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @forelse ($visitors as $visitor)
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $visitor->full_name }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->phone ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->company ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ \Illuminate\Support\Str::limit($visitor->purpose ?: '-', 40) }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->host_employee ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->house_address_or_unit ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ optional($visitor->check_in)->format('M j, Y H:i') }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ optional($visitor->check_out)->format('M j, Y H:i') ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->status }}</td>
                                        @if ($historyView !== 'active')
                                            <td class="px-6 py-4 text-slate-600">{{ optional($visitor->deleted_at)->format('M j, Y H:i') ?: '-' }}</td>
                                        @endif
                                        @if (auth()->user()->hasRole('security'))
                                            <td class="px-6 py-4">
                                                @if ($visitor->trashed())
                                                    <div class="flex flex-wrap gap-2">
                                                        <form method="POST" action="{{ route('visitors.restore', $visitor->visitor_id) }}">
                                                            @csrf
                                                            <input type="hidden" name="q" value="{{ $filterQ }}">
                                                            <input type="hidden" name="tab" value="history">
                                                            <input type="hidden" name="view" value="{{ $historyView }}">
                                                            <button class="px-3 py-2 text-xs font-semibold border rounded-lg border-emerald-200 text-emerald-700 hover:bg-emerald-50">Restore</button>
                                                        </form>
                                                        <button
                                                            type="button"
                                                            x-data
                                                            x-on:click="$dispatch('open-modal', 'force-delete-visitor-{{ $visitor->visitor_id }}')"
                                                            class="px-3 py-2 text-xs font-semibold border rounded-lg border-rose-200 text-rose-700 hover:bg-rose-50"
                                                        >
                                                            Force Delete
                                                        </button>
                                                    </div>
                                                @else
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="$dispatch('open-modal', 'archive-visitor-{{ $visitor->visitor_id }}')"
                                                        class="px-3 py-2 text-xs font-semibold border rounded-lg border-rose-200 text-rose-700 hover:bg-rose-50"
                                                    >
                                                        Archive
                                                    </button>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ auth()->user()->hasRole('security') ? ($historyView !== 'active' ? 11 : 10) : ($historyView !== 'active' ? 10 : 9) }}" class="px-6 py-10 text-center text-slate-500">No visitors found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    </div>
                </section>
            </div>

            @foreach ($pendingRequests as $req)
                @if ($req->id_photo_path)
                    <x-modal name="pending-id-photo-{{ $req->request_id }}" maxWidth="md" focusable>
                        <div class="p-6 bg-white text-center">
                            <h3 class="mb-4 text-lg font-semibold text-slate-900">{{ $req->visitor_name }} — ID Photo</h3>
                            <img src="{{ route('visitors.id-photo', $req->request_id) }}" alt="ID Photo" class="max-w-full mx-auto rounded-xl border border-slate-200">
                        </div>
                    </x-modal>
                @endif
            @endforeach

            @foreach ($visitors as $visitor)
                @if (auth()->user()->hasRole('security') && !$visitor->trashed())
                    <x-modal name="archive-visitor-{{ $visitor->visitor_id }}" maxWidth="md" focusable>
                        <div class="p-6 bg-white sm:p-8">
                            <div class="flex items-start gap-4">
                                <div class="flex items-center justify-center w-12 h-12 shrink-0 rounded-2xl bg-rose-100 text-rose-600">
                                    <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Archive Visitor?</h3>
                                    <p class="mt-2 text-sm text-slate-600">
                                        {{ $visitor->full_name }} will be removed from active history, but the record will stay available in the deleted view so it can still be restored later.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('visitors.destroy', $visitor) }}" class="flex flex-wrap justify-end gap-3 mt-6">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="q" value="{{ $filterQ }}">
                                <input type="hidden" name="tab" value="history">
                                <input type="hidden" name="view" value="{{ $historyView }}">

                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                                <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-rose-600 hover:bg-rose-700">
                                    Archive Visitor
                                </button>
                            </form>
                        </div>
                    </x-modal>
                @endif

                @if (auth()->user()->hasRole('security') && $visitor->trashed())
                    <x-modal name="force-delete-visitor-{{ $visitor->visitor_id }}" maxWidth="md" focusable>
                        <div class="p-6 bg-white sm:p-8">
                            <div class="flex items-start gap-4">
                                <div class="flex items-center justify-center w-12 h-12 shrink-0 rounded-2xl bg-rose-100 text-rose-700">
                                    <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Permanently Delete Visitor?</h3>
                                    <p class="mt-2 text-sm text-slate-600">
                                        This will permanently remove {{ $visitor->full_name }} from the database. This action cannot be undone.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('visitors.force-delete', $visitor->visitor_id) }}" class="flex flex-wrap justify-end gap-3 mt-6">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="q" value="{{ $filterQ }}">
                                <input type="hidden" name="tab" value="history">
                                <input type="hidden" name="view" value="{{ $historyView }}">

                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                                <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-rose-700 hover:bg-rose-800">
                                    Force Delete
                                </button>
                            </form>
                        </div>
                    </x-modal>
                @endif
            @endforeach
        </div>
    </div>
</x-app-layout>
