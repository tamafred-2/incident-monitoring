@php
    $ownerErrors = $errors->getBag('houseCreate');
    $ownerSections = [
        ['title' => 'Identity', 'description' => 'Basic resident information.', 'fields' => [
            ['surname', 'Surname', 'text', true, 50],
            ['first_name', 'First Name', 'text', true, 50],
            ['middle_name', 'Middle Name', 'text', false, 50],
            ['extension', 'Extension', 'text', false, 20],
        ]],
        ['title' => 'Contact', 'description' => 'Ways to reach the resident if follow-up is needed.', 'fields' => [
            ['phone', 'Phone', 'tel', true, 11],
            ['email', 'Email', 'email', true, 100],
        ]],
    ];
@endphp

<div class="space-y-4 border-t border-slate-200 pt-5">
    <div>
        <h4 class="text-base font-semibold text-slate-900">House Owner</h4>
        <p class="mt-1 text-sm text-slate-500">This resident will be assigned as the owner. Add other household members through Residents.</p>
    </div>
    @foreach ($ownerSections as $section)
        <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
            <h5 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">{{ $section['title'] }}</h5>
            <p class="mt-1 text-sm text-slate-500">{{ $section['description'] }}</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($section['fields'] as [$field, $label, $type, $required, $maxLength])
                    <div>
                        <label for="house-owner-{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                        <input
                            id="house-owner-{{ $field }}"
                            type="{{ $type }}"
                            name="owner[{{ $field }}]"
                            value="{{ $ownerErrors->any() ? old('owner.'.$field) : '' }}"
                            @required($required)
                            maxlength="{{ $maxLength }}"
                            @if ($field === 'phone') inputmode="numeric" pattern="[0-9]{11}" minlength="11" title="Enter exactly 11 digits." @endif
                            aria-invalid="{{ $ownerErrors->has('owner.'.$field) ? 'true' : 'false' }}"
                            @if ($ownerErrors->has('owner.'.$field)) aria-describedby="house-owner-{{ $field }}-error" @endif
                            class="mt-1 w-full rounded-xl {{ $ownerErrors->has('owner.'.$field) ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                        @if ($ownerErrors->has('owner.'.$field))
                            <p id="house-owner-{{ $field }}-error" class="mt-1 text-xs text-rose-600">{{ $ownerErrors->first('owner.'.$field) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
