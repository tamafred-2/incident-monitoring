@props([
    'user' => null,
    'passwordLabel' => 'Password',
    'passwordConfirmationLabel' => 'Confirm Password',
    'passwordRequired' => true,
])

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-slate-700">Surname</label>
        <input type="text" name="surname" required value="{{ old('surname', $user?->surname ?? '') }}"
               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">First Name</label>
        <input type="text" name="first_name" required value="{{ old('first_name', $user?->first_name ?? '') }}"
               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Middle Name</label>
        <input type="text" name="middle_name" value="{{ old('middle_name', $user?->middle_name ?? '') }}"
               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Extension</label>
        <input type="text" name="extension" value="{{ old('extension', $user?->extension ?? '') }}"
               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
    </div>
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">Email</label>
    <input type="email" name="email" required value="{{ old('email', $user?->email ?? '') }}"
           class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">Role</label>
    <select name="role" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
        @foreach (['admin', 'security', 'staff', 'investigator'] as $role)
            <option value="{{ $role }}" @selected(old('role', $user?->role ?? 'security') === $role)>{{ ucfirst($role) }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="block text-sm font-medium text-slate-700">Subdivision</label>
    <select name="subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
        <option value="">No subdivision / admin access</option>
        @foreach ($subdivisions as $subdivision)
            <option value="{{ $subdivision->subdivision_id }}"
                @selected((string) old('subdivision_id', $user?->subdivision_id ?? '') === (string) $subdivision->subdivision_id)>
                {{ $subdivision->subdivision_name }}
            </option>
        @endforeach
    </select>
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
