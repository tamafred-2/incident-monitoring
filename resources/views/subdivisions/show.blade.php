@php
    $isSecurityViewer = auth()->user()->isSecurity();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                    <h2 class="page-title">{{ $isSecurityViewer ? 'Contacts' : 'House Management' }}</h2>
                    <p class="page-subtitle">{{ $subdivision->subdivision_name }} - {{ $subdivision->full_address ?: 'No address provided.' }}</p>
                </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'subdivision-contact-info')"
                    class="inline-flex items-center px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                >
                    View Subdivision Contact Info
                </button>
                @if (auth()->user()->isAdmin())
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'edit-subdivision')"
                        class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100"
                    >
                        Edit Subdivision Profile
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="page-shell flex flex-col gap-4 min-[1600px]:gap-6">
            @include('partials.alerts')

            {{-- Houses --}}
            <div id="subdivision-houses-card">
            <div class="bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex flex-col gap-3 p-6 border-b border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ $isSecurityViewer ? 'Resident Contact Records' : 'House Records' }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $isSecurityViewer ? 'Browse houses and resident contact details for '.$subdivision->subdivision_name.'.' : 'Manage and maintain block and lot records for '.$subdivision->subdivision_name.'.' }}</p>
                    </div>
                    <div class="flex flex-wrap items-end gap-3">
                        <form method="GET" action="{{ route('subdivisions.show', $subdivision) }}" class="flex items-end gap-2">
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
                            <div class="min-w-[220px]">
                                <label class="block text-xs font-medium text-slate-500">Search</label>
                                <input type="search" name="q" value="{{ $filterQ }}" placeholder="Block, lot, or street"
                                       data-live-search="#subdivision-houses-card,#subdivision-house-modals" autocomplete="off"
                                       class="mt-1 w-full text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </form>
                        @if (auth()->user()->isAdmin())
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', 'create-house')"
                                class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800"
                            >
                                Add House Record
                            </button>
                        @endif
                    </div>
                </div>

                <x-scroll-list name="subdivision-houses" :records="$houses" modal-target="#subdivision-house-modals">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Block</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Lot</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Street</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Residents</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody data-scroll-rows id="subdivision-houses-rows" class="bg-white divide-y divide-slate-100">
@include('scroll-lists.subdivision-houses')
</tbody>
                    </table>
                </x-scroll-list>

            </div>
            </div>{{-- /#subdivision-houses-card --}}
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <x-modal name="edit-subdivision" :show="request()->boolean('edit') || ((string) old('edit_subdivision_id') === (string) $subdivision->subdivision_id)" maxWidth="2xl" focusable>
            <div class="p-6 bg-white sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Edit Subdivision Profile</h3>
                        <p class="mt-1 text-sm text-slate-500">Update details for {{ $subdivision->subdivision_name }}.</p>
                    </div>
                    <button type="button" x-on:click="$dispatch('close')" class="p-2 transition border rounded-xl border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700" aria-label="Close">
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('subdivisions.update', $subdivision) }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="edit_subdivision_id" value="{{ $subdivision->subdivision_id }}">
                    @include('subdivisions.partials.form-fields', ['subdivision' => $subdivision])
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800">Save Changes</button>
                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</button>
                    </div>
                </form>
            </div>
        </x-modal>

        {{-- Add House Modal --}}
        <x-modal name="create-house" :show="$errors->houseCreate->any()" maxWidth="2xl" focusable>
            <div
                class="p-6 bg-white sm:p-8"
                x-data
                x-on:open-modal.window="
                    if ($event.detail === 'create-house') {
                        $nextTick(() => {
                            $el.querySelectorAll('input:not([type=hidden])').forEach((i) => i.value = '');
                            $el.querySelectorAll('textarea').forEach((t) => t.value = '');
                            $el.querySelectorAll('select').forEach((s) => {
                                s.selectedIndex = 0;
                                s.dispatchEvent(new Event('change', { bubbles: true }));
                            });
                        });
                    }
                "
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Add House</h3>
                        <p class="mt-1 text-sm text-slate-500">Add a block and lot to {{ $subdivision->subdivision_name }}.</p>
                    </div>
                    <button type="button" x-on:click="$dispatch('close')" class="p-2 transition border rounded-xl border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700" aria-label="Close">
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('houses.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="house_form_mode" value="create">
                    <input type="hidden" name="subdivision_id" value="{{ $subdivision->subdivision_id }}">
                    <input type="hidden" name="_redirect" value="{{ route('subdivisions.show', $subdivision) }}">
                    @include('subdivisions.partials.house-form-fields', ['errorBag' => 'houseCreate'])
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800">Save House</button>
                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</button>
                    </div>
                </form>
            </div>
        </x-modal>

        {{-- Edit/Delete House Modals --}}
        <div id="subdivision-house-modals">
        @include('scroll-lists.subdivision-houses-modals')
        </div>{{-- /#subdivision-house-modals --}}
    @endif

    <x-modal name="subdivision-contact-info" maxWidth="4xl" focusable>
        <div class="p-6 bg-white sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Subdivision Contact Information</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $subdivision->subdivision_name }} registered call references</p>
                </div>
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="p-2 transition border rounded-xl border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                    aria-label="Close"
                >
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="grid gap-6 mt-6 lg:grid-cols-2">
                <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Primary Contact</h4>
                    <dl class="mt-4 grid grid-cols-[120px,minmax(0,1fr)] gap-x-4 gap-y-3 text-sm">
                        <dt class="text-slate-500">Person</dt>
                        <dd class="font-medium break-words text-slate-900">{{ $subdivision->contact_person ?: '-' }}</dd>
                        <dt class="text-slate-500">Registered Number</dt>
                        <dd class="font-medium break-words text-slate-900">{{ $subdivision->contact_number ?: '-' }}</dd>
                        <dt class="text-slate-500">Email</dt>
                        <dd class="font-medium break-all text-slate-900">{{ $subdivision->email ?: '-' }}</dd>
                    </dl>
                </div>

                <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Secondary Contact</h4>
                    <dl class="mt-4 grid grid-cols-[120px,minmax(0,1fr)] gap-x-4 gap-y-3 text-sm">
                        <dt class="text-slate-500">Person</dt>
                        <dd class="font-medium break-words text-slate-900">{{ $subdivision->secondary_contact_person ?: '-' }}</dd>
                        <dt class="text-slate-500">Registered Number</dt>
                        <dd class="font-medium break-words text-slate-900">{{ $subdivision->secondary_contact_number ?: '-' }}</dd>
                        <dt class="text-slate-500">Email</dt>
                        <dd class="font-medium break-all text-slate-900">{{ $subdivision->secondary_email ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </x-modal>

    @include('partials.live-search')
</x-app-layout>
