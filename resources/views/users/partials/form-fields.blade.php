@props([
    'user' => null,
    'passwordLabel' => 'Password',
    'passwordConfirmationLabel' => 'Confirm Password',
    'passwordRequired' => true,
])

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-slate-700">Surname</label>
        <input type="text" name="surname" required autocomplete="new-password" value="{{ old('surname', $user?->surname ?? '') }}"
               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">First Name</label>
        <input type="text" name="first_name" required autocomplete="new-password" value="{{ old('first_name', $user?->first_name ?? '') }}"
               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Middle Name</label>
        <input type="text" name="middle_name" autocomplete="new-password" value="{{ old('middle_name', $user?->middle_name ?? '') }}"
               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Extension</label>
        <input type="text" name="extension" autocomplete="new-password" value="{{ old('extension', $user?->extension ?? '') }}"
               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
    </div>
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">Email</label>
    <input type="email" name="email" required autocomplete="new-password" value="{{ old('email', $user?->email ?? '') }}"
           class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
</div>
@php
    $activeValue = old('is_active', $user?->is_active ?? true);
@endphp
<div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-slate-800">Account Availability</p>
            <p class="mt-1 text-xs text-slate-500">Set to inactive for leave/day off so this account cannot be assigned to incidents.</p>
        </div>
        <label x-data="{ enabled: {{ (string) $activeValue === '1' ? 'true' : 'false' }} }" class="inline-flex items-center gap-3 text-sm font-medium text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                class="peer sr-only"
                x-model="enabled"
                @checked((string) $activeValue === '1')
            >
            <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-emerald-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500 peer-focus:ring-offset-2">
                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200" :class="enabled ? 'translate-x-5' : 'translate-x-0'"></span>
            </span>
            <span class="min-w-16" x-text="enabled ? 'Active' : 'Inactive'"></span>
        </label>
    </div>
</div>

<div x-data="{ role: '{{ old('role', $user?->role ?? '') }}' }">
    <label class="block text-sm font-medium text-slate-700">Role</label>
    <select name="role" x-model="role" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
        <option value="">Select Role</option>
        @foreach (['admin', 'security', 'staff'] as $role)
            <option value="{{ $role }}" @selected(old('role', $user?->role ?? '') === $role)>{{ ucfirst($role) }}</option>
        @endforeach
    </select>

    <div x-show="role !== 'admin'" x-cloak class="mt-4">
        <input type="hidden" name="subdivision_id" value="{{ $subdivisions->first()?->subdivision_id ?? '' }}">
    </div>
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">{{ $passwordLabel }}</label>
    <input type="password" name="password" {{ $passwordRequired ? 'required' : '' }}
           class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">{{ $passwordConfirmationLabel }}</label>
    <input type="password" name="password_confirmation" {{ $passwordRequired ? 'required' : '' }}
           class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
</div>
