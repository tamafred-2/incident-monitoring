<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Subdivision</h2>
                <p class="mt-1 text-sm text-slate-500">Update details for {{ $subdivision->subdivision_name }}.</p>
            </div>
            <a href="{{ route('subdivisions.show', $subdivision) }}"
               class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl px-4 mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <form method="POST" action="{{ route('subdivisions.update', $subdivision) }}" class="space-y-6" x-data="{
                provinces: [],
                cities: [],
                barangays: [],
                selectedProvince: '{{ old('province', $subdivision->province) }}',
                selectedProvinceCode: '',
                selectedCity: '{{ old('city', $subdivision->city) }}',
                selectedCityCode: '',
                selectedStreet: '{{ old('street', $subdivision->street) }}',
                async loadProvinces() {
                    try {
                        const response = await fetch('https://psgc.gitlab.io/api/provinces/');
                        this.provinces = (await response.json()).sort((a, b) => a.name.localeCompare(b.name));
                    } catch (e) {
                        console.error(e);
                    }
                },
                                async loadCities(provinceCode) {
                    if (!provinceCode) { this.cities = []; return; }
                    try {
                        const response = await fetch('https://psgc.gitlab.io/api/cities-municipalities/');
                        const allCities = await response.json();
                        this.cities = allCities
                            .filter(item => item.provinceCode === provinceCode)
                            .sort((a, b) => a.name.localeCompare(b.name));
                    } catch (e) {
                        console.error(e);
                        this.cities = [];
                    }
                },
                async loadBarangays(cityCode) {
                    if (!cityCode) { this.barangays = []; return; }
                    try {
                        const response = await fetch('https://psgc.gitlab.io/api/barangays/');
                        const allBarangays = await response.json();
                        this.barangays = allBarangays
                            .filter(item => item.cityMunicipalityCode === cityCode || item.cityCode === cityCode || item.municipalityCode === cityCode)
                            .sort((a, b) => a.name.localeCompare(b.name));
                    } catch (e) {
                        console.error(e);
                        this.barangays = [];
                    }
                },
                loadCitiesForName(name) {
                    const prov = this.provinces.find(p => p.name === name);
                    if (prov) {
                        this.selectedProvinceCode = prov.code;
                        return this.loadCities(prov.code);
                    }
                    this.selectedProvinceCode = '';
                    this.cities = [];
                    return Promise.resolve();
                },
                loadBarangaysForCityName(name) {
                    const city = this.cities.find(c => c.name === name);
                    if (city) {
                        this.selectedCityCode = city.code;
                        return this.loadBarangays(city.code);
                    }
                    this.selectedCityCode = '';
                    this.barangays = [];
                    return Promise.resolve();
                }
            }"
            x-init="
                await loadProvinces();
                await new Promise(resolve => setTimeout(resolve, 100));
                if (selectedProvince) {
                    await loadCitiesForName(selectedProvince);
                    await new Promise(resolve => setTimeout(resolve, 100));
                }
                if (selectedCity) {
                    await loadBarangaysForCityName(selectedCity);
                }
            ">
                @csrf
                @method('PUT')

                {{-- Profile --}}
                <section class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Subdivision Profile</h4>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Subdivision Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="subdivision_name" required
                                   value="{{ old('subdivision_name', $subdivision->subdivision_name) }}"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                            @error('subdivision_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Status</label>
                            <select name="status" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                <option value="Active"   @selected(old('status', $subdivision->status) === 'Active')>Active</option>
                                <option value="Inactive" @selected(old('status', $subdivision->status) === 'Inactive')>Inactive</option>
                            </select>
                        </div>
                    </div>
                </section>

                {{-- Full Address --}}
                <section class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Full Address</h4>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Country <span class="text-rose-500">*</span></label>
                            <select name="country" required
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                <option value="Philippines" selected>Philippines</option>
                            </select>
                            @error('country') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Province / Region <span class="text-rose-500">*</span></label>
                            <select name="province" id="f-province" required
                                   x-model="selectedProvince"
                                   @change="selectedCity = ''; selectedStreet = ''; cities = []; barangays = []; loadCitiesForName(selectedProvince)"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                <option value="">Select Province</option>
                                @if($subdivision->province)
                                    <option value="{{ $subdivision->province }}" selected>{{ $subdivision->province }}</option>
                                @endif
                                <template x-for="prov in provinces" :key="prov.code">
                                    <option :value="prov.name" x-text="prov.name"></option>
                                </template>
                            </select>
                            @error('province') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">City / Municipality <span class="text-rose-500">*</span></label>
                            <select name="city" id="f-city" required
                                   x-model="selectedCity"
                                   @change="selectedStreet = ''; barangays = []; loadBarangaysForCityName(selectedCity)"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                <option value="">Select City</option>
                                @if($subdivision->city)
                                    <option value="{{ $subdivision->city }}" selected>{{ $subdivision->city }}</option>
                                @endif
                                <template x-for="city in cities" :key="city.code">
                                    <option :value="city.name" x-text="city.name"></option>
                                </template>
                            </select>
                            @error('city') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Street / Barangay <span class="text-rose-500">*</span></label>
                            <select name="street" id="f-street" required
                                   x-model="selectedStreet"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                <option value="">Select Barangay</option>
                                @if($subdivision->street)
                                    <option value="{{ $subdivision->street }}" selected>{{ $subdivision->street }}</option>
                                @endif
                                <template x-for="barangay in barangays" :key="barangay.code">
                                    <option :value="barangay.name" x-text="barangay.name"></option>
                                </template>
                            </select>
                            @error('street') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">ZIP Code <span class="text-rose-500">*</span></label>
                            <input type="text" name="zip" id="f-zip" required
                                   value="{{ old('zip', $subdivision->zip) }}"
                                   placeholder="e.g. 2418"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                            @error('zip') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- Primary Contact --}}
                <section class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Primary Contact <span class="text-rose-500">*</span></h4>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Contact Person <span class="text-rose-500">*</span></label>
                            <input type="text" name="contact_person" required
                                   value="{{ old('contact_person', $subdivision->contact_person) }}"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                            @error('contact_person') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Contact Number <span class="text-rose-500">*</span></label>
                            <input type="text" name="contact_number" required
                                   value="{{ old('contact_number', $subdivision->contact_number) }}"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                            @error('contact_number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Email <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" required
                                   value="{{ old('email', $subdivision->email) }}"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- Secondary Contact --}}
                <section
                    x-data="{ open: {{ ($subdivision->secondary_contact_person || $subdivision->secondary_contact_number || $subdivision->secondary_email || old('secondary_contact_person') || old('secondary_contact_number') || old('secondary_email')) ? 'true' : 'false' }} }"
                    class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Secondary Contact</h4>
                            <p class="mt-1 text-sm text-slate-500">Optional backup contact person.</p>
                        </div>
                        <button type="button" @click="open = !open"
                            class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                            x-text="open ? 'Remove' : 'Add Secondary Contact'">
                        </button>
                    </div>
                    <div x-show="open" x-cloak class="grid gap-4 mt-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Contact Person</label>
                            <input type="text" name="secondary_contact_person"
                                   value="{{ old('secondary_contact_person', $subdivision->secondary_contact_person) }}"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Contact Number</label>
                            <input type="text" name="secondary_contact_number"
                                   value="{{ old('secondary_contact_number', $subdivision->secondary_contact_number) }}"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Email</label>
                            <input type="email" name="secondary_email"
                                   value="{{ old('secondary_email', $subdivision->secondary_email) }}"
                                   class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        </div>
                    </div>
                </section>

                <div class="flex gap-3">
                    <button type="submit" class="rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Save Changes
                    </button>
                    <a href="{{ route('subdivisions.show', $subdivision) }}"
                       class="rounded-xl border border-slate-300 px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
