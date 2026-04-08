<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Subdivisions</h2>
            <p class="mt-1 text-sm text-slate-500">Manage subdivisions with quick create and modal editing.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            @if (auth()->user()->isAdmin())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <form method="GET" action="{{ route('subdivisions.index') }}" class="grid gap-4 md:grid-cols-[1fr_180px_160px_auto]">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Search</label>
                            <input type="search" name="q" value="{{ $filterQ }}" placeholder="Name, address, contact"
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
                            <label class="block text-sm font-medium text-slate-700">View</label>
                            <select name="view" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                <option value="active" @selected($filterView === 'active')>Active</option>
                                <option value="deleted" @selected($filterView === 'deleted')>Deleted</option>
                                <option value="all" @selected($filterView === 'all')>All</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-3">
                            <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Apply</button>
                            <a href="{{ route('subdivisions.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', 'create-subdivision')"
                                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                            >
                                Add Subdivision
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Name</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Address</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Contact</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Status</th>
                                    @if ($filterView !== 'active')
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Archived</th>
                                    @endif
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($subdivisions as $subdivision)
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $subdivision->subdivision_name }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $subdivision->address ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">
                                            {{ $subdivision->contact_person ?: '-' }}
                                            @if ($subdivision->contact_number)
                                                <span class="text-slate-400">|</span> {{ $subdivision->contact_number }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $subdivision->status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                                {{ $subdivision->status }}
                                            </span>
                                        </td>
                                        @if ($filterView !== 'active')
                                            <td class="px-6 py-4 text-slate-600">
                                                {{ $subdivision->deleted_at?->format('M j, Y H:i') ?? '-' }}
                                            </td>
                                        @endif
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <a
                                                    href="{{ route('subdivisions.show', array_merge(['subdivision' => $subdivision], array_filter(['q' => $filterQ, 'status' => $filterStatus, 'view' => $filterView !== 'active' ? $filterView : null], static fn ($value) => $value !== null && $value !== ''))) }}"
                                                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                                                >
                                                    View
                                                </a>
                                                @if (!$subdivision->trashed())
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="$dispatch('open-modal', 'edit-subdivision-{{ $subdivision->subdivision_id }}')"
                                                        class="inline-flex items-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 text-sm font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="$dispatch('open-modal', 'archive-subdivision-{{ $subdivision->subdivision_id }}')"
                                                        class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-800"
                                                    >
                                                        Delete
                                                    </button>
                                                @else
                                                <form method="POST" action="{{ route('subdivisions.restore', $subdivision->subdivision_id) }}">
                                                    @csrf
                                                    <input type="hidden" name="q" value="{{ $filterQ }}">
                                                        <input type="hidden" name="status" value="{{ $filterStatus }}">
                                                        <input type="hidden" name="view" value="{{ $filterView }}">
                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 hover:text-emerald-800"
                                                    >
                                                        Restore
                                                    </button>
                                                </form>
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="$dispatch('open-modal', 'force-delete-subdivision-{{ $subdivision->subdivision_id }}')"
                                                        class="inline-flex items-center rounded-xl border border-rose-300 bg-rose-600 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-rose-700"
                                                    >
                                                        Force Delete
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $filterView !== 'active' ? 6 : 5 }}" class="px-6 py-10 text-center text-slate-500">No subdivisions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <x-modal name="create-subdivision" :show="$errors->any() && old('edit_subdivision_id') === null" maxWidth="2xl" focusable>
                    <div class="bg-white p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Add Subdivision</h3>
                                <p class="mt-1 text-sm text-slate-500">Create a new subdivision record.</p>
                            </div>
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                                aria-label="Close create subdivision modal"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('subdivisions.store') }}" class="mt-6 space-y-4">
                            @csrf
                            @include('subdivisions.partials.form-fields')

                            <div class="flex flex-wrap gap-3 pt-2">
                                <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                    Save Subdivision
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

                @foreach ($subdivisions as $subdivision)
                    <x-modal name="edit-subdivision-{{ $subdivision->subdivision_id }}" :show="(string) old('edit_subdivision_id') === (string) $subdivision->subdivision_id" maxWidth="2xl" focusable>
                        <div class="bg-white p-6 sm:p-8">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Edit Subdivision</h3>
                                    <p class="mt-1 text-sm text-slate-500">Update details for {{ $subdivision->subdivision_name }}.</p>
                                </div>
                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                                    aria-label="Close edit subdivision modal"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <form method="POST" action="{{ route('subdivisions.update', $subdivision) }}" class="mt-6 space-y-4">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="edit_subdivision_id" value="{{ $subdivision->subdivision_id }}">

                                @include('subdivisions.partials.form-fields', ['subdivision' => $subdivision])

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

                    @if (!$subdivision->trashed())
                        <x-modal name="archive-subdivision-{{ $subdivision->subdivision_id }}" maxWidth="md" focusable>
                            <div class="bg-white p-6 sm:p-8">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                                        <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">Archive Subdivision?</h3>
                                        <p class="mt-2 text-sm text-slate-600">
                                            {{ $subdivision->subdivision_name }} will be hidden from active lists, but it will stay available in the deleted view for history and restore.
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('subdivisions.destroy', $subdivision) }}" class="mt-6 flex flex-wrap justify-end gap-3">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="q" value="{{ $filterQ }}">
                                    <input type="hidden" name="status" value="{{ $filterStatus }}">
                                    <input type="hidden" name="view" value="{{ $filterView }}">

                                    <button
                                        type="button"
                                        x-on:click="$dispatch('close')"
                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        Cancel
                                    </button>
                                    <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                                        Archive Subdivision
                                    </button>
                                </form>
                            </div>
                        </x-modal>
                    @endif

                    @if ($subdivision->trashed())
                        <x-modal name="force-delete-subdivision-{{ $subdivision->subdivision_id }}" maxWidth="md" focusable>
                            <div class="bg-white p-6 sm:p-8">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                                        <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">Permanently Delete Subdivision?</h3>
                                        <p class="mt-2 text-sm text-slate-600">
                                            This will permanently remove {{ $subdivision->subdivision_name }} from the database. This action cannot be undone.
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('subdivisions.force-delete', $subdivision->subdivision_id) }}" class="mt-6 flex flex-wrap justify-end gap-3">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="q" value="{{ $filterQ }}">
                                    <input type="hidden" name="status" value="{{ $filterStatus }}">
                                    <input type="hidden" name="view" value="{{ $filterView }}">

                                    <button
                                        type="button"
                                        x-on:click="$dispatch('close')"
                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        Cancel
                                    </button>
                                    <button class="rounded-xl bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">
                                        Force Delete
                                    </button>
                                </form>
                            </div>
                        </x-modal>
                    @endif
                @endforeach
            @else
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Name</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Address</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Contact</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Status</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($subdivisions as $subdivision)
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $subdivision->subdivision_name }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $subdivision->address ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $subdivision->contact_person ?: '-' }}</td>
                                        <td class="px-6 py-4">{{ $subdivision->status }}</td>
                                        <td class="px-6 py-4">
                                            <a
                                                href="{{ route('subdivisions.show', $subdivision) }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                                            >
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-slate-500">No subdivision assigned.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
