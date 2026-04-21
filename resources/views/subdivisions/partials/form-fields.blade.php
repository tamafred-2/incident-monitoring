@props([
    'subdivision' => null,
])

@php
    $streetOptions = ['Imperial Street', 'Plaza Boulevard'];
    $streetValue = old('street', $subdivision?->street ?? '');
    $streetSelection = in_array($streetValue, $streetOptions, true)
        ? $streetValue
        : ($streetValue !== '' ? 'others' : '');
@endphp

<div class="space-y-4" x-data="{ streetSelection: '{{ $streetSelection }}' }">
    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Branding</h4>
            <p class="mt-1 text-sm text-slate-500">Used as the app logo and subdivision name across the system, including login.</p>
        </div>
        <div class="grid gap-4 md:grid-cols-[120px_1fr]">
            <div>
                <div class="h-24 w-24 overflow-hidden rounded-full border border-slate-200 bg-slate-50">
                    <img
                        src="{{ $subdivision?->logo_url ?? asset('imgsrc/logo.png') }}"
                        alt="{{ old('subdivision_name', $subdivision?->subdivision_name ?? 'Subdivision') }} logo"
                        class="h-full w-full object-cover"
                    >
                </div>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Subdivision Logo</label>
                    <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp"
                           class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG, or WEBP. Max 2 MB.</p>
                    @error('logo') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                @if ($subdivision?->logo_path)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))
                               class="rounded border-slate-300 text-rose-600 shadow-sm focus:ring-rose-500">
                        Remove current logo
                    </label>
                @endif
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Subdivision Profile</h4>
            <p class="mt-1 text-sm text-slate-500">Core subdivision details used across monitoring and account assignment.</p>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Subdivision Name <span class="text-rose-500">*</span></label>
                <input type="text" name="subdivision_name" required
                       value="{{ old('subdivision_name', $subdivision?->subdivision_name ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                @error('subdivision_name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Status</label>
                <select name="status" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="Active" @selected(old('status', $subdivision?->status ?? 'Active') === 'Active')>Active</option>
                    <option value="Inactive" @selected(old('status', $subdivision?->status ?? '') === 'Inactive')>Inactive</option>
                </select>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Full Address</h4>
            <p class="mt-1 text-sm text-slate-500">Complete location of the subdivision.</p>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Country <span class="text-rose-500">*</span></label>
                <input type="text" name="country" required
                       value="{{ old('country', $subdivision?->country ?? 'Philippines') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                @error('country') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Street <span class="text-rose-500">*</span></label>
                <select
                    x-model="streetSelection"
                    class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                >
                    <option value="">Select Street</option>
                    @foreach ($streetOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                    <option value="others">Others</option>
                </select>

                <template x-if="streetSelection === 'others'">
                    <input
                        type="text"
                        name="street"
                        id="edit-street"
                        required
                        value="{{ $streetSelection === 'others' ? $streetValue : '' }}"
                        placeholder="Type street name"
                        class="mt-3 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                    >
                </template>

                <template x-if="streetSelection !== 'others'">
                    <input type="hidden" name="street" :value="streetSelection">
                </template>

                @error('street') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">City / Municipality <span class="text-rose-500">*</span></label>
                <input type="text" name="city" id="edit-city" required
                       value="{{ old('city', $subdivision?->city ?? '') }}"
                       placeholder="e.g. Caloocan City"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                @error('city') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Province / Region <span class="text-rose-500">*</span></label>
                <input type="text" name="province" id="edit-province" required
                       value="{{ old('province', $subdivision?->province ?? '') }}"
                       placeholder="e.g. Metro Manila"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                @error('province') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">ZIP Code <span class="text-rose-500">*</span></label>
                <input type="text" name="zip" id="edit-zip" required
                       value="{{ old('zip', $subdivision?->zip ?? '') }}"
                       placeholder="e.g. 1400"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                @error('zip') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Primary Contact <span class="text-rose-500">*</span></h4>
            <p class="mt-1 text-sm text-slate-500">Main point of contact for the subdivision.</p>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Contact Person <span class="text-rose-500">*</span></label>
                <input type="text" name="contact_person" required
                       value="{{ old('contact_person', $subdivision?->contact_person ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                @error('contact_person') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Contact Number <span class="text-rose-500">*</span></label>
                <input type="text" name="contact_number" required
                       value="{{ old('contact_number', $subdivision?->contact_number ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                @error('contact_number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Email <span class="text-rose-500">*</span></label>
                <input type="email" name="email" required
                       value="{{ old('email', $subdivision?->email ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section
        x-data="{ open: {{ ($subdivision?->secondary_contact_person || $subdivision?->secondary_contact_number || $subdivision?->secondary_email || old('secondary_contact_person') || old('secondary_contact_number') || old('secondary_email')) ? 'true' : 'false' }} }"
        class="rounded-2xl border border-slate-200 bg-white p-5"
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
        <div x-show="open" x-cloak class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Contact Person</label>
                <input type="text" name="secondary_contact_person"
                       value="{{ old('secondary_contact_person', $subdivision?->secondary_contact_person ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Contact Number</label>
                <input type="text" name="secondary_contact_number"
                       value="{{ old('secondary_contact_number', $subdivision?->secondary_contact_number ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="secondary_email"
                       value="{{ old('secondary_email', $subdivision?->secondary_email ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
        </div>
    </section>
</div>
