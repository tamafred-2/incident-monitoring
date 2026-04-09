<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">House Management</h2>
            <p class="mt-1 text-sm text-slate-500">Maintain unique block and lot records for each subdivision.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('houses.index') }}" class="grid gap-4 md:grid-cols-[1fr_240px_auto]">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Search</label>
                        <input type="search" name="q" value="{{ $filterQ }}" placeholder="Subdivision, block, or lot"
                               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                        <select name="subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">All subdivisions</option>
                            @foreach ($subdivisions as $subdivision)
                                <option value="{{ $subdivision->subdivision_id }}" @selected((string) $filterSubdivision === (string) $subdivision->subdivision_id)>
                                    {{ $subdivision->subdivision_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Apply</button>
                        <a href="{{ route('houses.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
                        <button
                            type="button"
                            x-data
                            x-on:click="$dispatch('open-modal', 'create-house')"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Add House
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Subdivision</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Block</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Lot</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Address</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($houses as $house)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $house->subdivision?->subdivision_name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $house->block }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $house->lot }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $house->display_address }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-nowrap items-center gap-3">
                                            <a
                                                href="{{ route('houses.show', array_merge(['house' => $house], array_filter(['q' => $filterQ, 'subdivision_id' => $filterSubdivision], static fn ($value) => $value !== null && $value !== '' && $value !== 0 && $value !== '0'))) }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                                            >
                                                View
                                            </a>
                                            <button
                                                type="button"
                                                x-data
                                                x-on:click="$dispatch('open-modal', 'edit-house-{{ $house->house_id }}')"
                                                class="inline-flex items-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 text-sm font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                x-data
                                                x-on:click="$dispatch('open-modal', 'delete-house-{{ $house->house_id }}')"
                                                class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-800"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-slate-500">No house records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-modal name="create-house" :show="$errors->any() && old('edit_house_id') === null" maxWidth="2xl" focusable>
                <div class="bg-white p-6 sm:p-8" x-data x-on:open-modal.window="if ($event.detail === 'create-house') { $nextTick(() => { $el.querySelectorAll('input:not([type=hidden])').forEach(i => i.value = ''); $el.querySelectorAll('textarea').forEach(t => t.value = ''); $el.querySelectorAll('select').forEach(s => s.selectedIndex = 0); }); }">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Add House</h3>
                            <p class="mt-1 text-sm text-slate-500">Create a unique block and lot record for a subdivision.</p>
                        </div>
                        <button
                            type="button"
                            x-on:click="$dispatch('close')"
                            class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                            aria-label="Close create house modal"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('houses.store') }}" class="mt-6 space-y-4">
                        @csrf
                        @include('houses.partials.form-fields')

                        <div class="flex flex-wrap gap-3 pt-2">
                            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                Save House
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

            @foreach ($houses as $house)
                <x-modal name="edit-house-{{ $house->house_id }}" :show="(string) old('edit_house_id') === (string) $house->house_id" maxWidth="2xl" focusable>
                    <div class="bg-white p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Edit House</h3>
                                <p class="mt-1 text-sm text-slate-500">Update {{ $house->display_address }}.</p>
                            </div>
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                                aria-label="Close edit house modal"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('houses.update', $house) }}" class="mt-6 space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="edit_house_id" value="{{ $house->house_id }}">

                            @include('houses.partials.form-fields', ['house' => $house])

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

                <x-modal name="delete-house-{{ $house->house_id }}" maxWidth="md" focusable>
                    <div class="bg-white p-6 sm:p-8">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Delete House?</h3>
                                <p class="mt-2 text-sm text-slate-600">
                                    {{ $house->display_address }} will be removed from the subdivision house registry.
                                </p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('houses.destroy', $house) }}" class="mt-6 flex flex-wrap justify-end gap-3">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="q" value="{{ $filterQ }}">
                            <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">

                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                                Delete House
                            </button>
                        </form>
                    </div>
                </x-modal>
            @endforeach
        </div>
    </div>
</x-app-layout>
