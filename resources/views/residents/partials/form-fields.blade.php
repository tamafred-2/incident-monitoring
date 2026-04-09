@props([
    'resident' => null,
    'subdivisions' => collect(),
    'houses' => collect(),
])

@php
    $selectedSubdivision = (string) old('subdivision_id', $resident?->subdivision_id ?? '');
    $selectedHouse = (string) old('house_id', $resident?->house_id ?? '');
@endphp

<div
    x-data="{
        subdivisionId: '{{ $selectedSubdivision }}',
        houseId: '{{ $selectedHouse }}'
    }"
    class="space-y-4"
>
    @php
        $nameParts = $resident?->name_parts ?? [
            'surname' => null,
            'first_name' => null,
            'middle_name' => null,
            'extension' => null,
        ];
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Identity</h4>
            <p class="mt-1 text-sm text-slate-500">Basic resident information and code assignment.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Surname</label>
                <input
                    type="text"
                    name="surname"
                    required
                    value="{{ old('surname', $nameParts['surname']) }}"
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">First Name</label>
                <input
                    type="text"
                    name="first_name"
                    required
                    value="{{ old('first_name', $nameParts['first_name']) }}"
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Middle Name</label>
                <input
                    type="text"
                    name="middle_name"
                    value="{{ old('middle_name', $nameParts['middle_name']) }}"
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Extension</label>
                <input
                    type="text"
                    name="extension"
                    value="{{ old('extension', $nameParts['extension']) }}"
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Resident Code</label>
                <input
                    type="text"
                    name="resident_code"
                    required
                    value="{{ old('resident_code', $resident?->resident_code ?? '') }}"
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Status</label>
                <select
                    name="status"
                    required
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
                    @foreach (['Active', 'Inactive'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $resident?->status ?? 'Active') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Contact</h4>
            <p class="mt-1 text-sm text-slate-500">Ways to reach the resident if follow-up is needed.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Phone</label>
                <input
                    type="text"
                    name="phone"
                    value="{{ old('phone', $resident?->phone ?? '') }}"
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $resident?->email ?? '') }}"
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Residency</h4>
            <p class="mt-1 text-sm text-slate-500">Assign the resident to a subdivision and, if available, to a managed house record.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                <select
                    name="subdivision_id"
                    x-model="subdivisionId"
                    @change="if (houseId) { const selectedOption = $event.target.options[$event.target.selectedIndex]; if (selectedOption && selectedOption.value !== '') { const currentHouse = $refs.houseSelect.options[$refs.houseSelect.selectedIndex]; if (currentHouse && currentHouse.dataset.subdivision !== selectedOption.value) { houseId = ''; } } }"
                    required
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
                    <option value="">Select Subdivision</option>
                    @foreach ($subdivisions as $subdivision)
                        <option value="{{ $subdivision->subdivision_id }}" @selected($selectedSubdivision === (string) $subdivision->subdivision_id)>
                            {{ $subdivision->subdivision_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Assigned House</label>
                <select
                    x-ref="houseSelect"
                    name="house_id"
                    x-model="houseId"
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
                    <option value="">No assigned house</option>
                    @foreach ($houses as $house)
                        <option
                            value="{{ $house->house_id }}"
                            data-subdivision="{{ $house->subdivision_id }}"
                            x-show="!subdivisionId || subdivisionId === '{{ $house->subdivision_id }}'"
                            @selected($selectedHouse === (string) $house->house_id)
                        >
                            {{ $house->subdivision?->subdivision_name ?? 'No subdivision' }} - {{ $house->display_address }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">When a house is selected, the resident address will follow the managed house record.</p>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium text-slate-700">Manual Address / Unit</label>
            <input
                type="text"
                name="address_or_unit"
                :disabled="houseId !== ''"
                value="{{ old('address_or_unit', $resident?->address_or_unit ?? '') }}"
                placeholder="Optional if no house is assigned"
                class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 disabled:cursor-not-allowed disabled:bg-slate-100"
            >
        </div>
    </section>
</div>
