@props([
    'subdivision' => null,
])

<div class="space-y-4">
    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Subdivision Profile</h4>
            <p class="mt-1 text-sm text-slate-500">Core subdivision details used across monitoring and account assignment.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Subdivision Name</label>
                <input type="text" name="subdivision_name" required
                       value="{{ old('subdivision_name', $subdivision?->subdivision_name ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Address</label>
                <input type="text" name="address" value="{{ old('address', $subdivision?->address ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
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
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Contact Details</h4>
            <p class="mt-1 text-sm text-slate-500">Primary contact information for the subdivision or property management team.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Contact Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person', $subdivision?->contact_person ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number', $subdivision?->contact_number ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email', $subdivision?->email ?? '') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
        </div>
    </section>
</div>
