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
    $selectedResidentId = (string) old('resident_id', $user?->resident_id ?? '');
@endphp

<div x-data="{ role: '{{ old('role', $user?->role ?? '') }}' }">
    <label class="block text-sm font-medium text-slate-700">Role</label>
    <select name="role" x-model="role" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
        <option value="">Select Role</option>
        @foreach (['admin', 'security', 'staff', 'investigator', 'resident'] as $role)
            <option value="{{ $role }}" @selected(old('role', $user?->role ?? '') === $role)>{{ ucfirst($role) }}</option>
        @endforeach
    </select>

    <div x-show="role !== 'admin' && role !== 'resident'" x-cloak class="mt-4">
        <label class="block text-sm font-medium text-slate-700">Subdivision</label>
        <select name="subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            <option value="" @selected(old('subdivision_id', $user?->subdivision_id ?? '') === '')>Select Subdivision</option>
            @foreach ($subdivisions as $subdivision)
                <option value="{{ $subdivision->subdivision_id }}"
                    @selected((string) old('subdivision_id', $user?->subdivision_id ?? '') === (string) $subdivision->subdivision_id)>
                    {{ $subdivision->subdivision_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div x-show="role === 'resident'" x-cloak class="mt-4">
        <label class="block text-sm font-medium text-slate-700">Resident Linked To House</label>
        <select name="resident_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            <option value="">Select resident with house</option>
            @foreach ($residents as $resident)
                <option value="{{ $resident->resident_id }}" @selected($selectedResidentId === (string) $resident->resident_id)>
                    {{ $resident->full_name }} - {{ $resident->subdivision?->subdivision_name ?? 'No subdivision' }}{{ $resident->house?->display_address ? ' - ' . $resident->house->display_address : '' }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">This list comes from existing Resident records that are already assigned to a managed house. The subdivision for the account will come from that house.</p>
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
