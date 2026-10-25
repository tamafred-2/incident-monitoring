@php($profile = $profile ?? null)
<div x-data="{ advanceRegistration: @js((bool) old('advance_registration', false)) }" class="grid gap-4 sm:grid-cols-2">
    @if (!$profile)
        <label class="sm:col-span-2 flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="advance_registration" value="1" x-model="advanceRegistration">Register for an expected visit ? collect the ID photo on arrival</label>
    @endif
    @foreach ([['surname', 'Surname', true, 50], ['first_name', 'First Name', true, 50], ['middle_initials', 'Middle Initials', false, 20], ['extension', 'Extension', false, 20], ['phone', 'Phone', true, 11]] as [$field, $label, $required, $length])
        <div>
            <label for="profile-{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
            <input id="profile-{{ $field }}" name="{{ $field }}" type="{{ $field === 'phone' ? 'tel' : 'text' }}" value="{{ old($field, $profile?->$field) }}" @required($required) maxlength="{{ $length }}" @if ($field === 'phone') inputmode="numeric" pattern="[0-9]{11}" minlength="11" title="Enter exactly 11 digits." @endif class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500">
            <x-input-error :messages="$errors->get($field)" class="mt-1" />
        </div>
    @endforeach
    <div>
        <label for="profile-photo" class="block text-sm font-medium text-slate-700">ID Photo</label>
        <input id="profile-photo" name="id_photo" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" data-photo-upload data-max-mb="{{ \App\Support\PhotoUpload::MAX_MB }}" :required="!advanceRegistration && @js(!$profile)" class="mt-2 w-full text-sm text-slate-600">
        <p class="mt-1 text-xs text-slate-500">Photos only: JPG/JPEG or PNG (maximum {{ \App\Support\PhotoUpload::MAX_MB }} MB). Videos are not allowed.</p>
        <p class="mt-1 text-xs text-slate-500">{{ $profile ? 'Leave blank to keep the current photo.' : 'An ID photo is required at check-in. For an expected visit, you may collect it on arrival.' }}</p>
        <x-input-error :messages="$errors->get('id_photo')" class="mt-1" />
    </div>
</div>
