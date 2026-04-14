@props([
    'user' => null,
    'allowExistingResidentLink' => false,
    'lockResidentRecord' => false,
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
    $residentMode = old('resident_mode', $selectedResidentId ? 'existing' : 'new');
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

    <div x-show="role === 'resident'" x-cloak class="mt-4 space-y-4">
        @if ($lockResidentRecord)
            <input type="hidden" name="resident_id" value="{{ $user?->resident_id ?? '' }}">

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <label class="block text-sm font-medium text-slate-700">Linked Resident</label>
                <p class="mt-2 text-sm text-slate-700">
                    {{ $user?->resident?->full_name ?? 'No resident record linked.' }}
                    @if ($user?->resident?->subdivision)
                        - {{ $user->resident->subdivision->subdivision_name }}
                    @endif
                    @if ($user?->resident?->house?->display_address)
                        · {{ $user->resident->house->display_address }}
                    @endif
                </p>
            </div>
        @elseif ($allowExistingResidentLink)
            <div class="flex gap-4">
                <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="radio" name="resident_mode" value="existing"
                        {{ $residentMode === 'existing' ? 'checked' : '' }}
                        onchange="document.getElementById('resident-existing-section').style.display='block'; document.getElementById('resident-new-section').style.display='none';"
                        class="border-slate-300 text-sky-600 focus:ring-sky-500">
                    Link existing resident
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="radio" name="resident_mode" value="new"
                        {{ $residentMode === 'new' ? 'checked' : '' }}
                        onchange="document.getElementById('resident-existing-section').style.display='none'; document.getElementById('resident-new-section').style.display='block';"
                        class="border-slate-300 text-sky-600 focus:ring-sky-500">
                    Create new resident
                </label>
            </div>

            <div id="resident-existing-section" style="display: {{ $residentMode === 'existing' ? 'block' : 'none' }}">
                <label class="block text-sm font-medium text-slate-700">Resident Record</label>
                <select name="resident_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">Select resident</option>
                    @foreach ($residents as $resident)
                        @php
                            $isLinkedToAnotherUser = $resident->user && (string) $resident->resident_id !== (string) ($user?->resident_id ?? '');
                        @endphp
                        <option
                            value="{{ $resident->resident_id }}"
                            @selected($selectedResidentId === (string) $resident->resident_id)
                            @disabled($isLinkedToAnotherUser)
                        >
                            {{ $resident->full_name }} - {{ $resident->subdivision?->subdivision_name ?? 'No subdivision' }}{{ $resident->house?->display_address ? ' · ' . $resident->house->display_address : '' }}{{ $isLinkedToAnotherUser ? ' (already linked)' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Residents with an active account are marked as already linked.</p>
            </div>
        @else
            <input type="hidden" name="resident_mode" value="new">
            <div id="resident-new-section" style="display: block" class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                        <select name="new_resident_subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">Select subdivision</option>
                            @foreach ($subdivisions as $subdivision)
                                <option value="{{ $subdivision->subdivision_id }}" @selected((string) old('new_resident_subdivision_id') === (string) $subdivision->subdivision_id)>
                                    {{ $subdivision->subdivision_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">House (optional)</label>
                        <select name="new_resident_house_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">No house assigned</option>
                            @foreach ($houses as $house)
                                <option value="{{ $house->house_id }}" @selected((string) old('new_resident_house_id') === (string) $house->house_id)>
                                    {{ $house->subdivision?->subdivision_name }} - {{ $house->display_address }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Phone (optional)</label>
                    <input type="text" name="new_resident_phone" value="{{ old('new_resident_phone') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
            </div>
        @endif

        @if ($allowExistingResidentLink)
            <div id="resident-new-section" style="display: {{ $residentMode === 'new' ? 'block' : 'none' }}" class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                        <select name="new_resident_subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">Select subdivision</option>
                            @foreach ($subdivisions as $subdivision)
                                <option value="{{ $subdivision->subdivision_id }}" @selected((string) old('new_resident_subdivision_id') === (string) $subdivision->subdivision_id)>
                                    {{ $subdivision->subdivision_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">House (optional)</label>
                        <select name="new_resident_house_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">No house assigned</option>
                            @foreach ($houses as $house)
                                <option value="{{ $house->house_id }}" @selected((string) old('new_resident_house_id') === (string) $house->house_id)>
                                    {{ $house->subdivision?->subdivision_name }} - {{ $house->display_address }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Phone (optional)</label>
                    <input type="text" name="new_resident_phone" value="{{ old('new_resident_phone') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
            </div>
        @endif
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
