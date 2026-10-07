@props([
    'resident' => null,
    'subdivisions' => collect(),
    'houses' => collect(),
    'withAccount' => false,
])

@php
    $isSubmittedForm = $errors->any() && ($resident
        ? (string) old('edit_resident_id') === (string) $resident->resident_id
        : old('edit_resident_id') === null);
    $formErrors = $isSubmittedForm ? $errors->getBag('default') : new \Illuminate\Support\MessageBag;
    $fieldValue = fn ($field, $default = null) => $isSubmittedForm ? old($field, $default) : $default;
    $fieldPrefix = 'resident-'.($resident?->resident_id ?? 'create');
    $selectedSubdivision = (string) $fieldValue('subdivision_id', $resident?->subdivision_id ?? '');
    $selectedHouse = (string) $fieldValue('house_id', $resident?->house_id ?? '');
@endphp

<div
    x-data="{
        subdivisionId: '{{ $selectedSubdivision }}',
        houseId: '{{ $selectedHouse }}'
    }"
    class="space-y-4"
>
    @php
        $nameParts = $resident?->name_parts ?? [
            'surname' => null,
            'first_name' => null,
            'middle_name' => null,
            'extension' => null,
        ];
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Identity</h4>
            <p class="mt-1 text-sm text-slate-500">Basic resident information.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Surname</label>
                <input
                    type="text"
                    name="surname"
                    aria-invalid="{{ $formErrors->has('surname') ? 'true' : 'false' }}"
                    aria-describedby="{{ $fieldPrefix }}-surname-error"
                    required
                    value="{{ $fieldValue('surname', $nameParts['surname']) }}"
                    class="mt-1 w-full rounded-xl {{ $formErrors->has('surname') ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm {{ $formErrors->has('surname') ? 'focus:border-rose-500' : 'focus:border-brand-500' }} focus:ring-brand-500"
                >
                @if ($formErrors->has('surname'))
                    <p id="{{ $fieldPrefix }}-surname-error" data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('surname') }}</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">First Name</label>
                <input
                    type="text"
                    name="first_name"
                    aria-invalid="{{ $formErrors->has('first_name') ? 'true' : 'false' }}"
                    aria-describedby="{{ $fieldPrefix }}-first_name-error"
                    required
                    value="{{ $fieldValue('first_name', $nameParts['first_name']) }}"
                    class="mt-1 w-full rounded-xl {{ $formErrors->has('first_name') ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm {{ $formErrors->has('first_name') ? 'focus:border-rose-500' : 'focus:border-brand-500' }} focus:ring-brand-500"
                >
                @if ($formErrors->has('first_name'))
                    <p id="{{ $fieldPrefix }}-first_name-error" data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('first_name') }}</p>
                @endif
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Middle Name</label>
                <input
                    type="text"
                    name="middle_name"
                    aria-invalid="{{ $formErrors->has('middle_name') ? 'true' : 'false' }}"
                    aria-describedby="{{ $fieldPrefix }}-middle_name-error"
                    value="{{ $fieldValue('middle_name', $nameParts['middle_name']) }}"
                    class="mt-1 w-full rounded-xl {{ $formErrors->has('middle_name') ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm {{ $formErrors->has('middle_name') ? 'focus:border-rose-500' : 'focus:border-brand-500' }} focus:ring-brand-500"
                >
                @if ($formErrors->has('middle_name'))
                    <p id="{{ $fieldPrefix }}-middle_name-error" data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('middle_name') }}</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Extension</label>
                <input
                    type="text"
                    name="extension"
                    aria-invalid="{{ $formErrors->has('extension') ? 'true' : 'false' }}"
                    aria-describedby="{{ $fieldPrefix }}-extension-error"
                    value="{{ $fieldValue('extension', $nameParts['extension']) }}"
                    class="mt-1 w-full rounded-xl {{ $formErrors->has('extension') ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm {{ $formErrors->has('extension') ? 'focus:border-rose-500' : 'focus:border-brand-500' }} focus:ring-brand-500"
                >
                @if ($formErrors->has('extension'))
                    <p id="{{ $fieldPrefix }}-extension-error" data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('extension') }}</p>
                @endif
            </div>
        </div>

        @if ($resident)
            <div class="mt-4 md:max-w-sm">
                <label class="block text-sm font-medium text-slate-700">Status</label>
                <select
                    name="status"
                    aria-invalid="{{ $formErrors->has('status') ? 'true' : 'false' }}"
                    aria-describedby="{{ $fieldPrefix }}-status-error"
                    required
                    class="mt-1 w-full rounded-xl {{ $formErrors->has('status') ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm {{ $formErrors->has('status') ? 'focus:border-rose-500' : 'focus:border-brand-500' }} focus:ring-brand-500"
                >
                    @foreach (\App\Enums\ActiveStatus::values() as $status)
                        <option value="{{ $status }}" @selected($fieldValue('status', $resident->status?->value ?? 'Active') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                @if ($formErrors->has('status'))
                    <p id="{{ $fieldPrefix }}-status-error" data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('status') }}</p>
                @endif
            </div>
        @else
            <input type="hidden" name="status" value="Active">
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Contact</h4>
            <p class="mt-1 text-sm text-slate-500">Ways to reach the resident if follow-up is needed.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Phone</label>
                <input
                    type="tel"
                    name="phone"
                    aria-invalid="{{ $formErrors->has('phone') ? 'true' : 'false' }}"
                    aria-describedby="{{ $fieldPrefix }}-phone-error"
                    required
                    value="{{ $fieldValue('phone', $resident?->phone ?? '') }}"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="20"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    class="mt-1 w-full rounded-xl {{ $formErrors->has('phone') ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm {{ $formErrors->has('phone') ? 'focus:border-rose-500' : 'focus:border-brand-500' }} focus:ring-brand-500"
                >
                @if ($formErrors->has('phone'))
                    <p id="{{ $fieldPrefix }}-phone-error" data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('phone') }}</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input
                    type="email"
                    name="email"
                    aria-invalid="{{ $formErrors->has('email') ? 'true' : 'false' }}"
                    aria-describedby="{{ $fieldPrefix }}-email-error"
                    required
                    value="{{ $fieldValue('email', $resident?->email ?? '') }}"
                    class="mt-1 w-full rounded-xl {{ $formErrors->has('email') ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm {{ $formErrors->has('email') ? 'focus:border-rose-500' : 'focus:border-brand-500' }} focus:ring-brand-500"
                >
                @if ($formErrors->has('email'))
                    <p id="{{ $fieldPrefix }}-email-error" data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('email') }}</p>
                @endif
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
        <div class="mb-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Residency</h4>
            <p class="mt-1 text-sm text-slate-500">Assign the resident to a subdivision and, if available, to a managed house record.</p>
        </div>

        @if ($formErrors->has('subdivision_id'))
            <p data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('subdivision_id') }}</p>
        @endif
        <div class="grid gap-4 md:grid-cols-2">
            <input type="hidden" name="subdivision_id" value="{{ $selectedSubdivision !== '' ? $selectedSubdivision : $subdivisions->first()?->subdivision_id }}">
            <div>
                <label class="block text-sm font-medium text-slate-700">Assigned House</label>
                <select
                    x-ref="houseSelect"
                    name="house_id"
                    aria-invalid="{{ $formErrors->has('house_id') ? 'true' : 'false' }}"
                    aria-describedby="{{ $fieldPrefix }}-house_id-error"
                    x-model="houseId"
                    class="mt-1 w-full rounded-xl {{ $formErrors->has('house_id') ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm {{ $formErrors->has('house_id') ? 'focus:border-rose-500' : 'focus:border-brand-500' }} focus:ring-brand-500"
                >
                    <option value="">No assigned house</option>
                    @foreach ($houses as $house)
                        <option
                            value="{{ $house->house_id }}"
                            data-subdivision="{{ $house->subdivision_id }}"
                            @selected($selectedHouse === (string) $house->house_id)
                        >
                            {{ $house->display_address }}
                        </option>
                    @endforeach
                </select>
                @if ($formErrors->has('house_id'))
                    <p id="{{ $fieldPrefix }}-house_id-error" data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('house_id') }}</p>
                @endif
                <p class="mt-1 text-xs text-slate-500">When a house is selected, the resident address will follow the managed house record.</p>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium text-slate-700">Manual Address / Unit</label>
            <input
                type="text"
                name="address_or_unit"
                    aria-invalid="{{ $formErrors->has('address_or_unit') ? 'true' : 'false' }}"
                    aria-describedby="{{ $fieldPrefix }}-address_or_unit-error"
                :disabled="houseId !== ''"
                value="{{ $fieldValue('address_or_unit', $resident?->address_or_unit ?? '') }}"
                placeholder="Optional if no house is assigned"
                class="mt-1 w-full rounded-xl {{ $formErrors->has('address_or_unit') ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm {{ $formErrors->has('address_or_unit') ? 'focus:border-rose-500' : 'focus:border-brand-500' }} focus:ring-brand-500 disabled:cursor-not-allowed disabled:bg-slate-100"
            >
                @if ($formErrors->has('address_or_unit'))
                    <p id="{{ $fieldPrefix }}-address_or_unit-error" data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('address_or_unit') }}</p>
                @endif
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium text-slate-700">Relation to Head/Owner</label>
            @php
                $relationOptions = ['Owner', 'Husband', 'Wife', 'Child', 'Relative', 'Friend', 'Tenant', 'Helper'];
                $savedRelation = $fieldValue('relation_to_owner', $resident?->relation_to_owner ?? '');
                $isCustomRelation = $savedRelation !== '' && !in_array($savedRelation, $relationOptions, true);
                $selectedRelation = $isCustomRelation ? 'Other' : $savedRelation;
                $customRelation = $fieldValue('relation_to_owner_other', $isCustomRelation ? $savedRelation : '');
            @endphp
            <div x-data="{ relation: @js($selectedRelation) }" class="space-y-3">
                <select
                    name="relation_to_owner"
                    x-model="relation"
                    class="mt-1 w-full rounded-xl text-sm shadow-sm focus:ring-brand-500 @if ($formErrors->has('relation_to_owner')) border-rose-300 focus:border-rose-500 @else border-slate-300 focus:border-brand-500 @endif"
                >
                    <option value="">Select relation</option>
                    @foreach ($relationOptions as $option)
                        <option value="{{ $option }}" @selected($selectedRelation === $option)>{{ $option }}</option>
                    @endforeach
                    <option value="Other" @selected($selectedRelation === 'Other')>Other</option>
                </select>
                <input
                    type="text"
                    name="relation_to_owner_other"
                    x-cloak
                    x-show="relation === 'Other'"
                    value="{{ $customRelation }}"
                    placeholder="Specify relation"
                    class="w-full rounded-xl text-sm shadow-sm focus:ring-brand-500 @if ($formErrors->has('relation_to_owner_other')) border-rose-300 focus:border-rose-500 @else border-slate-300 focus:border-brand-500 @endif"
                >
            </div>
            @if ($formErrors->has('relation_to_owner'))
                <p data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('relation_to_owner') }}</p>
            @endif
            @if ($formErrors->has('relation_to_owner_other'))
                <p data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('relation_to_owner_other') }}</p>
            @endif
            <p class="mt-1 text-xs text-slate-500">Define how this resident is related to the lot/house head or owner.</p>
        </div>
    </section>

    @if ($withAccount)
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-4">
                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Account Access</h4>
                @if ($resident?->user)
                    <p class="mt-1 text-sm text-slate-500">Linked account: <span class="font-medium text-slate-700">{{ $resident->user->email }}</span></p>
                @else
                    <p class="mt-1 text-sm text-slate-500">Optionally create a login account for this resident.</p>
                @endif
            </div>

            @if (!$resident?->user)
                <div
                    x-data="{ createAccount: {{ $fieldValue('account_email') ? 'true' : 'false' }} }"
                    class="space-y-4"
                >
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" x-model="createAccount" class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500">
                        <span class="text-sm font-medium text-slate-700">Create login account</span>
                    </label>

                    <div x-show="createAccount" x-cloak class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Login Email</label>
                            <input
                                type="email"
                                name="account_email"
                                value="{{ $fieldValue('account_email') }}"
                                :required="createAccount"
                                class="mt-1 w-full rounded-xl text-sm shadow-sm focus:ring-brand-500 @if ($formErrors->has('account_email')) border-rose-300 focus:border-rose-500 @else border-slate-300 focus:border-brand-500 @endif"
                            >
                            @if ($formErrors->has('account_email'))
                                <p data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('account_email') }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Password</label>
                            <input
                                type="password"
                                name="account_password"
                    aria-invalid="{{ $formErrors->has('account_password') ? 'true' : 'false' }}"
                    aria-describedby="{{ $fieldPrefix }}-account_password-error"
                                :required="createAccount"
                                class="mt-1 w-full rounded-xl {{ $formErrors->has('account_password') ? 'border-rose-300' : 'border-slate-300' }} text-sm shadow-sm {{ $formErrors->has('account_password') ? 'focus:border-rose-500' : 'focus:border-brand-500' }} focus:ring-brand-500"
                            >
                @if ($formErrors->has('account_password'))
                    <p id="{{ $fieldPrefix }}-account_password-error" data-validation-error class="mt-1 text-xs text-rose-600">{{ $formErrors->first('account_password') }}</p>
                @endif
                        </div>
                    </div>
                </div>
            @endif
        </section>
    @endif
</div>
