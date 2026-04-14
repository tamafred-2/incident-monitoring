<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Residents</h2>
            <p class="mt-1 text-sm text-slate-500">Manage resident records, house assignments, and QR cards in one place.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('residents.index') }}" class="grid gap-4 md:grid-cols-[1fr_180px_220px_auto]">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Search</label>
                        <input type="search" name="q" value="{{ $filterQ }}" placeholder="Name, code, address, house"
                               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">All</option>
                            <option value="Active" @selected($filterStatus === 'Active')>Active</option>
                            <option value="Inactive" @selected($filterStatus === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                        <select name="subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">{{ auth()->user()->isAdmin() ? 'All visible subdivisions' : 'Assigned subdivision' }}</option>
                            @foreach ($subdivisions as $subdivision)
                                <option value="{{ $subdivision->subdivision_id }}" @selected($filterSubdivision === $subdivision->subdivision_id)>{{ $subdivision->subdivision_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Apply</button>
                        <a href="{{ route('residents.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
                        @if (auth()->user()->isAdmin())
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', 'create-resident')"
                                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                            >
                                Add Resident
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Name</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">House</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Legacy Address</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Code</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Status</th>
                                @if ($subdivisions->isNotEmpty())
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Subdivision</th>
                                @endif
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($residents as $resident)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $resident->full_name }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $resident->house?->display_address ?: '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $resident->address_or_unit ?: '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600"><code>{{ $resident->resident_code }}</code></td>
                                    <td class="px-6 py-4 text-slate-600">{{ $resident->status }}</td>
                                    @if ($subdivisions->isNotEmpty())
                                        <td class="px-6 py-4 text-slate-600">{{ $resident->subdivision->subdivision_name ?? '-' }}</td>
                                    @endif
                                    <td class="px-6 py-4">
                                        <div class="flex flex-nowrap items-center gap-3">
                                            <a
                                                href="{{ route('residents.show', array_merge(['resident' => $resident], array_filter(['q' => $filterQ, 'status' => $filterStatus, 'subdivision_id' => $filterSubdivision], static fn ($value) => $value !== null && $value !== '' && $value !== 0))) }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                                            >
                                                View
                                            </a>
                                            @if (auth()->user()->isAdmin())
                                                <button
                                                    type="button"
                                                    x-data
                                                    x-on:click="$dispatch('open-modal', 'edit-resident-{{ $resident->resident_id }}')"
                                                    class="inline-flex items-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 text-sm font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800"
                                                >
                                                    Edit
                                                </button>
                                            @endif
                                            <a
                                                href="{{ route('residents.qr-card', $resident) }}"
                                                target="_blank"
                                                class="inline-flex items-center rounded-xl border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                QR Card
                                            </a>
                                            @if (auth()->user()->isAdmin())
                                                <button
                                                    type="button"
                                                    x-data
                                                    x-on:click="$dispatch('open-modal', 'delete-resident-{{ $resident->resident_id }}')"
                                                    class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-800"
                                                >
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $subdivisions->isNotEmpty() ? 7 : 6 }}" class="px-6 py-10 text-center text-slate-500">No residents found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (auth()->user()->isAdmin())
                <x-modal name="create-resident" :show="$errors->any() && old('edit_resident_id') === null" maxWidth="3xl" focusable>
                    <div class="bg-white p-6 sm:p-8" x-data x-on:open-modal.window="if ($event.detail === 'create-resident') { $nextTick(() => { $el.querySelectorAll('input:not([type=hidden]):not([type=checkbox])').forEach(i => i.value = ''); $el.querySelectorAll('input[type=checkbox]').forEach(i => i.checked = false); $el.querySelectorAll('textarea').forEach(t => t.value = ''); $el.querySelectorAll('select').forEach(s => s.selectedIndex = 0); }); }">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Add Resident</h3>
                                <p class="mt-1 text-sm text-slate-500">Create a resident profile and optionally assign a managed house.</p>
                            </div>
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                                aria-label="Close create resident modal"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('residents.store') }}" class="mt-6 space-y-4">
                            @csrf
                            @include('residents.partials.form-fields', [
                                'resident' => null,
                                'subdivisions' => $subdivisions,
                                'houses' => $houses,
                                'withAccount' => true,
                            ])

                            <div class="flex flex-wrap gap-3 pt-2">
                                <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                    Save Resident
                                </button>
                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </x-modal>
            @endif

            @foreach ($residents as $resident)
                @if (auth()->user()->isAdmin())
                    <x-modal name="edit-resident-{{ $resident->resident_id }}" :show="(string) old('edit_resident_id') === (string) $resident->resident_id" maxWidth="3xl" focusable>
                        <div class="bg-white p-6 sm:p-8">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Edit Resident</h3>
                                    <p class="mt-1 text-sm text-slate-500">Update {{ $resident->full_name }} and the assigned housing record.</p>
                                </div>
                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                                    aria-label="Close edit resident modal"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <form method="POST" action="{{ route('residents.update', $resident) }}" class="mt-6 space-y-4">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="edit_resident_id" value="{{ $resident->resident_id }}">

                                @include('residents.partials.form-fields', ['resident' => $resident, 'subdivisions' => $subdivisions, 'houses' => $houses])

                                <div class="flex flex-wrap gap-3 pt-2">
                                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                        Save Changes
                                    </button>
                                    <button
                                        type="button"
                                        x-on:click="$dispatch('close')"
                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </x-modal>

                    <x-modal name="delete-resident-{{ $resident->resident_id }}" maxWidth="md" focusable>
                        <div class="bg-white p-6 sm:p-8">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Delete Resident?</h3>
                                    <p class="mt-2 text-sm text-slate-600">
                                        {{ $resident->full_name }} will be removed from resident monitoring if there are no linked accounts or verified incidents.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('residents.destroy', $resident) }}" class="mt-6 flex flex-wrap justify-end gap-3">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="q" value="{{ $filterQ }}">
                                <input type="hidden" name="status" value="{{ $filterStatus }}">
                                <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">

                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                                <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                                    Delete Resident
                                </button>
                            </form>
                        </div>
                    </x-modal>
                @endif
            @endforeach
        </div>
    </div>
</x-app-layout>
