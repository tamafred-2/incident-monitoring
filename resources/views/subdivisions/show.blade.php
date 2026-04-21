<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <img src="{{ $subdivision->logo_url }}" alt="{{ $subdivision->subdivision_name }} logo" class="object-cover border rounded-full h-14 w-14 border-slate-200">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $subdivision->subdivision_name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $subdivision->full_address ?: 'No address provided.' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('dashboard') }}"
                    class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                >
                    View Dashboard Summary
                </a>
                @if (auth()->user()->isAdmin())
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'edit-subdivision')"
                        class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100"
                    >
                        Edit Subdivision
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="flex flex-col gap-6 px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Contact Information</h4>
                        <p class="mt-1 text-sm text-slate-500">Contact details are available on-demand to keep this page clean.</p>
                    </div>
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'subdivision-contact-info')"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                    >
                        View Contact Info
                    </button>
                </div>
            </div>

            {{-- Houses --}}
            <div class="bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex flex-col gap-3 p-6 border-b border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Houses</h3>
                    <div class="flex items-center gap-3">
                        <form method="GET" action="{{ route('subdivisions.show', $subdivision) }}" class="flex items-center gap-2">
                            <input type="search" name="q" value="{{ $filterQ }}" placeholder="Search block or lot"
                                   class="text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                            <button class="px-3 py-2 text-sm font-semibold text-white rounded-xl bg-sky-600 hover:bg-sky-700">Search</button>
                            @if ($filterQ)
                                <a href="{{ route('subdivisions.show', $subdivision) }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50">Clear</a>
                            @endif
                        </form>
                        @if (auth()->user()->isAdmin())
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', 'create-house')"
                                class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800"
                            >
                                Add House
                            </button>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Block</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Lot</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Street</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Residents</th>
                                @if (auth()->user()->isAdmin())
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse ($houses as $house)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $house->block }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $house->lot }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $house->street ?: '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $house->residents->count() }}</td>
                                    @if (auth()->user()->isAdmin())
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <a
                                                    href="{{ route('houses.show', $house) }}"
                                                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    View
                                                </a>
                                                <button
                                                    type="button"
                                                    x-data
                                                    x-on:click="$dispatch('open-modal', 'edit-house-{{ $house->house_id }}')"
                                                    class="inline-flex items-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 text-sm font-semibold text-sky-700 transition hover:bg-sky-100"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    x-data
                                                    x-on:click="$dispatch('open-modal', 'delete-house-{{ $house->house_id }}')"
                                                    class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->isAdmin() ? 5 : 4 }}" class="px-6 py-10 text-center text-slate-500">No houses found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <x-modal name="edit-subdivision" :show="request()->boolean('edit') || ((string) old('edit_subdivision_id') === (string) $subdivision->subdivision_id)" maxWidth="2xl" focusable>
            <div class="p-6 bg-white sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Edit Subdivision</h3>
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
        <x-modal name="create-house" :show="$errors->any() && old('edit_house_id') === null && !$errors->has('subdivision_name')" maxWidth="2xl" focusable>
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
                    <input type="hidden" name="subdivision_id" value="{{ $subdivision->subdivision_id }}">
                    <input type="hidden" name="_redirect" value="{{ route('subdivisions.show', $subdivision) }}">
                    @include('subdivisions.partials.house-form-fields')
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800">Save House</button>
                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</button>
                    </div>
                </form>
            </div>
        </x-modal>

        {{-- Edit/Delete House Modals --}}
        @foreach ($houses as $house)
            <x-modal name="edit-house-{{ $house->house_id }}" :show="(string) old('edit_house_id') === (string) $house->house_id" maxWidth="2xl" focusable>
                <div class="p-6 bg-white sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Edit House</h3>
                            <p class="mt-1 text-sm text-slate-500">Update {{ $house->display_address }}.</p>
                        </div>
                        <button type="button" x-on:click="$dispatch('close')" class="p-2 transition border rounded-xl border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700" aria-label="Close">
                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('houses.update', $house) }}" class="mt-6 space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="edit_house_id" value="{{ $house->house_id }}">
                        <input type="hidden" name="subdivision_id" value="{{ $subdivision->subdivision_id }}">
                        <input type="hidden" name="_redirect" value="{{ route('subdivisions.show', $subdivision) }}">
                        @include('subdivisions.partials.house-form-fields', ['house' => $house])
                        <div class="flex flex-wrap gap-3 pt-2">
                            <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800">Save Changes</button>
                            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</button>
                        </div>
                    </form>
                </div>
            </x-modal>

            <x-modal name="delete-house-{{ $house->house_id }}" maxWidth="md" focusable>
                <div class="p-6 bg-white sm:p-8">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-12 h-12 shrink-0 rounded-2xl bg-rose-100 text-rose-600">
                            <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Delete House?</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ $house->display_address }} will be removed from the subdivision house registry.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('houses.destroy', $house) }}" class="flex flex-wrap justify-end gap-3 mt-6">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="_redirect" value="{{ route('subdivisions.show', $subdivision) }}">
                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-rose-600 hover:bg-rose-700">Delete House</button>
                    </form>
                </div>
            </x-modal>
        @endforeach
    @endif

    <x-modal name="subdivision-contact-info" maxWidth="2xl" focusable>
        <div class="p-6 bg-white sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Contact Information</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $subdivision->subdivision_name }}</p>
                </div>
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="p-2 transition border rounded-xl border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
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
                    <dl class="mt-4 grid grid-cols-[auto,1fr] gap-x-4 gap-y-3 text-sm">
                        <dt class="text-slate-500">Person</dt>
                        <dd class="font-medium text-right break-words text-slate-900">{{ $subdivision->contact_person ?: '-' }}</dd>
                        <dt class="text-slate-500">Number</dt>
                        <dd class="font-medium text-right break-words text-slate-900">{{ $subdivision->contact_number ?: '-' }}</dd>
                        <dt class="text-slate-500">Email</dt>
                        <dd class="font-medium text-right break-all text-slate-900">{{ $subdivision->email ?: '-' }}</dd>
                    </dl>
                </div>

                <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Secondary Contact</h4>
                    <dl class="mt-4 grid grid-cols-[auto,1fr] gap-x-4 gap-y-3 text-sm">
                        <dt class="text-slate-500">Person</dt>
                        <dd class="font-medium text-right break-words text-slate-900">{{ $subdivision->secondary_contact_person ?: '-' }}</dd>
                        <dt class="text-slate-500">Number</dt>
                        <dd class="font-medium text-right break-words text-slate-900">{{ $subdivision->secondary_contact_number ?: '-' }}</dd>
                        <dt class="text-slate-500">Email</dt>
                        <dd class="font-medium text-right break-all text-slate-900">{{ $subdivision->secondary_email ?: '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </x-modal>

</x-app-layout>
