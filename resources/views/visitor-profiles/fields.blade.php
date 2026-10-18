@php($profile = $profile ?? null)
<div class="grid gap-4 sm:grid-cols-2">
    @foreach ([['surname', 'Surname', true, 50], ['first_name', 'First Name', true, 50], ['middle_initials', 'Middle Initials', false, 20], ['extension', 'Extension', false, 20], ['phone', 'Phone', true, 20]] as [$field, $label, $required, $length])
        <div>
            <label for="profile-{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
            <input id="profile-{{ $field }}" name="{{ $field }}" type="{{ $field === 'phone' ? 'tel' : 'text' }}" value="{{ old($field, $profile?->$field) }}" @required($required) maxlength="{{ $length }}" @if ($field === 'phone') inputmode="numeric" pattern="[0-9]+" @endif class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500">
            <x-input-error :messages="$errors->get($field)" class="mt-1" />
        </div>
    @endforeach
    <div>
        <label for="profile-photo" class="block text-sm font-medium text-slate-700">ID Photo</label>
        <input id="profile-photo" name="id_photo" type="file" accept="image/jpeg,image/png,image/webp,image/gif" @required(!$profile) class="mt-2 w-full text-sm text-slate-600">
        <p class="mt-1 text-xs text-slate-500">{{ $profile ? 'Leave blank to keep the current photo.' : 'Register an ID photo once. It can be reused on future visits.' }}</p>
        <x-input-error :messages="$errors->get('id_photo')" class="mt-1" />
    </div>
</div>
