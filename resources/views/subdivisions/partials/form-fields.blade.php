@props([
    'subdivision' => null,
])

<div>
    <label class="block text-sm font-medium text-slate-700">Subdivision Name</label>
    <input type="text" name="subdivision_name" required
           value="{{ old('subdivision_name', $subdivision?->subdivision_name ?? '') }}"
           class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">Address</label>
    <input type="text" name="address" value="{{ old('address', $subdivision?->address ?? '') }}"
           class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
</div>
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
<div>
    <label class="block text-sm font-medium text-slate-700">Email</label>
    <input type="email" name="email" value="{{ old('email', $subdivision?->email ?? '') }}"
           class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">Status</label>
    <select name="status" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
        <option value="Active" @selected(old('status', $subdivision?->status ?? 'Active') === 'Active')>Active</option>
        <option value="Inactive" @selected(old('status', $subdivision?->status ?? '') === 'Inactive')>Inactive</option>
    </select>
</div>
