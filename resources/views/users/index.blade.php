<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Users</h2>
            <p class="mt-1 text-sm text-slate-500">Admin-managed accounts with quick create and modal editing.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('users.index') }}" class="grid gap-4 md:grid-cols-[1fr_160px_160px_auto]">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Search</label>
                        <input type="search" name="q" value="{{ $filterQ }}" placeholder="Name or email"
                               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Role</label>
                        <select name="role" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">All</option>
                            @foreach (['admin', 'security', 'staff'] as $role)
                                <option value="{{ $role }}" @selected($filterRole === $role)>{{ ucfirst($role) }}</option>
                            @endforeach
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
                        <a href="{{ route('users.index', ['per_page' => $perPage]) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
                        <button
                            type="button"
                            x-data
                            x-on:click="$dispatch('open-modal', 'create-user')"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Add User
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 px-6 py-4 border-b border-slate-200 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">User Directory</h3>
                        <p class="mt-1 text-sm text-slate-500">Manage admin, security, and staff accounts from one table.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Name</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Email</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Role</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Availability</th>
                                @if ($filterView !== 'active')
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Archived</th>
                                @endif
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="min-w-[12rem]">
                                            <div class="font-medium text-slate-900">{{ $user->full_name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $user->subdivision?->subdivision_name ?? ($user->role === 'admin' ? 'All subdivisions' : 'Unassigned subdivision') }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        <div class="max-w-[16rem] truncate" title="{{ $user->email }}">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        <span class="inline-flex whitespace-nowrap rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($user->role) }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    @if ($filterView !== 'active')
                                        <td class="px-6 py-4 text-slate-600">
                                            @if ($user->deleted_at)
                                                <div class="min-w-[9rem]">
                                                    <div class="whitespace-nowrap font-medium text-slate-700">{{ $user->deleted_at->format('M j, Y') }}</div>
                                                    <div class="mt-1 whitespace-nowrap text-xs text-slate-500">{{ $user->deleted_at->format('h:i A') }}</div>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <a
                                                href="{{ route('users.show', array_merge(['user' => $user], array_filter(['q' => $filterQ, 'role' => $filterRole, 'subdivision_id' => $filterSubdivision, 'view' => $filterView !== 'active' ? $filterView : null, 'per_page' => $perPage], static fn ($value) => $value !== null && $value !== ''))) }}"
                                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                                            >
                                                View
                                            </a>
                                            @if (!$user->trashed())
                                                <button
                                                    type="button"
                                                    x-data
                                                    x-on:click="$dispatch('open-modal', 'edit-user-{{ $user->user_id }}')"
                                                    class="inline-flex items-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 text-sm font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800"
                                                >
                                                    Edit
                                                </button>
                                                @if (auth()->id() !== $user->user_id)
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="$dispatch('open-modal', 'archive-user-{{ $user->user_id }}')"
                                                        class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-800"
                                                    >
                                                        Delete
                                                    </button>
                                                @endif
                                            @else
                                                <form method="POST" action="{{ route('users.restore', $user->user_id) }}">
                                                    @csrf
                                                    <input type="hidden" name="q" value="{{ $filterQ }}">
                                                    <input type="hidden" name="role" value="{{ $filterRole }}">
                                                    <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                                    <input type="hidden" name="view" value="{{ $filterView }}">
                                                    <input type="hidden" name="per_page" value="{{ $perPage }}">
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
                                                    x-on:click="$dispatch('open-modal', 'force-delete-user-{{ $user->user_id }}')"
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
                                    <td colspan="{{ $filterView !== 'active' ? 6 : 5 }}" class="px-6 py-10 text-center text-slate-500">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 px-6 py-4 border-t border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">
                        @if ($users->total() > 0)
                            Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }} users
                        @else
                            No user records to paginate
                        @endif
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="GET" action="{{ route('users.index') }}" class="flex items-center gap-2">
                            @if ($filterQ !== '')
                                <input type="hidden" name="q" value="{{ $filterQ }}">
                            @endif
                            @if ($filterRole !== '')
                                <input type="hidden" name="role" value="{{ $filterRole }}">
                            @endif
                            @if ($filterSubdivision !== null && $filterSubdivision !== '')
                                <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                            @endif
                            <input type="hidden" name="view" value="{{ $filterView }}">
                            <label for="users-rows-per-page" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Rows</label>
                            <input
                                id="users-rows-per-page"
                                type="text"
                                name="per_page"
                                list="users-row-size-options"
                                value=""
                                placeholder="{{ $perPage }}"
                                class="w-24 rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                aria-label="Rows per page"
                                inputmode="numeric"
                                autocomplete="off"
                                pattern="[0-9]{1,3}"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3)"
                                onchange="if (this.value.trim() !== '') { this.form.requestSubmit(); }"
                                onkeydown="if (event.key === 'Enter') { event.preventDefault(); if (this.value.trim() !== '') { this.form.requestSubmit(); } }"
                            >
                            <datalist id="users-row-size-options">
                                <option value="10"></option>
                                <option value="25"></option>
                                <option value="50"></option>
                                <option value="100"></option>
                            </datalist>
                        </form>
                        @if ($users->onFirstPage())
                            <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></span>
                        @else
                            <a href="{{ $users->previousPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></a>
                        @endif
                        <span class="px-3 py-2 text-sm font-semibold rounded-xl bg-slate-100 text-slate-700">
                            Page {{ $users->currentPage() }} of {{ max($users->lastPage(), 1) }}
                        </span>
                        @if ($users->hasMorePages())
                            <a href="{{ $users->nextPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></a>
                        @else
                            <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></span>
                        @endif
                    </div>
                </div>
            </div>

            <x-modal name="create-user" :show="$errors->any() && old('edit_user_id') === null" maxWidth="2xl" focusable>
                <div class="bg-white p-6 sm:p-8" x-data x-on:open-modal.window="if ($event.detail === 'create-user') { $nextTick(() => { $el.querySelectorAll('input:not([type=hidden]):not([type=checkbox])').forEach(i => i.value = ''); $el.querySelectorAll('input[type=checkbox]').forEach(i => i.checked = false); $el.querySelectorAll('select').forEach(s => s.selectedIndex = 0); const activeToggle = $el.querySelector('input[name=&quot;is_active&quot;][type=&quot;checkbox&quot;]'); if (activeToggle) activeToggle.checked = true; }); }">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Add User</h3>
                            <p class="mt-1 text-sm text-slate-500">Create a new user account.</p>
                        </div>
                        <button
                            type="button"
                            x-on:click="$dispatch('close')"
                            class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                            aria-label="Close create user modal"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('users.store') }}" class="mt-6 space-y-4">
                        @csrf
                        @include('users.partials.form-fields')

                        <div class="flex flex-wrap gap-3 pt-2">
                            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                Create User
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

            @foreach ($users as $user)
                <x-modal name="edit-user-{{ $user->user_id }}" :show="(string) old('edit_user_id') === (string) $user->user_id" maxWidth="2xl" focusable>
                    <div class="bg-white p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Edit User</h3>
                                <p class="mt-1 text-sm text-slate-500">Update account details for {{ $user->full_name }}.</p>
                            </div>
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                                aria-label="Close edit user modal"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('users.update', $user) }}" class="mt-6 space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="edit_user_id" value="{{ $user->user_id }}">

                            @include('users.partials.form-fields', [
                                'user' => $user,
                                'passwordLabel' => 'New Password',
                                'passwordConfirmationLabel' => 'Confirm New Password',
                                'passwordRequired' => false,
                            ])

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

                @if (!$user->trashed() && auth()->id() !== $user->user_id)
                    <x-modal name="archive-user-{{ $user->user_id }}" maxWidth="md" focusable>
                        <div class="bg-white p-6 sm:p-8">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Archive User?</h3>
                                    <p class="mt-2 text-sm text-slate-600">
                                        {{ $user->full_name }} will be removed from active lists, but their record will stay available in the deleted view for history and restore.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-6 flex flex-wrap justify-end gap-3">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="q" value="{{ $filterQ }}">
                                <input type="hidden" name="role" value="{{ $filterRole }}">
                                <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                <input type="hidden" name="view" value="{{ $filterView }}">
                                <input type="hidden" name="per_page" value="{{ $perPage }}">

                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                                <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                                    Archive User
                                </button>
                            </form>
                        </div>
                    </x-modal>
                @endif

                @if ($user->trashed())
                    <x-modal name="force-delete-user-{{ $user->user_id }}" maxWidth="md" focusable>
                        <div class="bg-white p-6 sm:p-8">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Permanently Delete User?</h3>
                                    <p class="mt-2 text-sm text-slate-600">
                                        This will permanently remove {{ $user->full_name }} from the database. This action cannot be undone.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('users.force-delete', $user->user_id) }}" class="mt-6 flex flex-wrap justify-end gap-3">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="q" value="{{ $filterQ }}">
                                <input type="hidden" name="role" value="{{ $filterRole }}">
                                <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                <input type="hidden" name="view" value="{{ $filterView }}">
                                <input type="hidden" name="per_page" value="{{ $perPage }}">

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
        </div>
    </div>
</x-app-layout>
