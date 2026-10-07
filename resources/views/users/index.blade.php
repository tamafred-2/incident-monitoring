<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="page-title">Users</h2>
            <p class="page-subtitle">Admin-managed accounts with quick create and modal editing.</p>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="page-shell flex flex-col gap-4 min-[1600px]:gap-6">
            @include('partials.alerts')

            @if (session('generated_password'))
                <div
                    x-data="{
                        open: true,
                        password: @js(session('generated_password')),
                        copied: false,
                        copy() {
                            navigator.clipboard.writeText(this.password).then(() => {
                                this.copied = true;
                                setTimeout(() => this.copied = false, 2000);
                            });
                        }
                    }"
                    x-cloak
                    x-show="open"
                    x-transition.opacity
                    @keydown.escape.window="open = false"
                    class="fixed inset-0 z-50 flex items-center justify-center px-4"
                    role="dialog"
                    aria-modal="true"
                >
                    <div class="absolute inset-0 bg-slate-950/80" @click="open = false"></div>

                    <div
                        x-show="open"
                        x-transition.scale.origin.center
                        class="relative w-full max-w-md rounded-2xl bg-white p-7 text-center shadow-2xl"
                    >
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-7 w-7 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <h3 class="mt-4 text-lg font-semibold text-slate-900">Account created</h3>
                        <p class="mt-1 text-sm text-slate-500">Generated password for the new user</p>

                        <div class="mt-5 flex items-center justify-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                            <code class="font-mono text-lg font-bold tracking-wide text-slate-900" x-text="password"></code>
                        </div>

                        <button
                            type="button"
                            @click="copy()"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.379a3 3 0 00-.879-2.121L10.5 5.379A3 3 0 008.379 4.5H7v-1z" />
                                <path d="M4.5 6A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h7a1.5 1.5 0 001.5-1.5v-5.879a1.5 1.5 0 00-.44-1.06L9.44 6.439A1.5 1.5 0 008.378 6H4.5z" />
                            </svg>
                            <span x-show="!copied">Copy password</span>
                            <span x-show="copied" x-cloak>Copied!</span>
                        </button>

                        <p class="mt-4 text-xs leading-5 text-slate-400">
                            Copy it now and give it to the user. For security it is shown only once &mdash; once you close, leave, or refresh this page it cannot be retrieved again.
                        </p>

                        <button
                            type="button"
                            @click="open = false"
                            class="mt-3 text-sm font-medium text-slate-500 transition hover:text-slate-700"
                        >
                            Close
                        </button>
                    </div>
                </div>
            @endif

            <div id="users-results" class="flex flex-col gap-6">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 px-6 py-4 border-b border-slate-200 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">User Directory</h3>
                        <p class="mt-1 text-sm text-slate-500">Manage admin, security, and staff accounts from one table.</p>
                    </div>
                    <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap items-end gap-3">
                        <div class="min-w-[150px]">
                            <label class="block text-xs font-medium text-slate-500">Role</label>
                            <select name="role" onchange="this.form.requestSubmit()" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">All roles</option>
                                @foreach (['admin', 'security', 'staff'] as $role)
                                    <option value="{{ $role }}" @selected($filterRole === $role)>{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-[140px]">
                            <label class="block text-xs font-medium text-slate-500">View</label>
                            <select name="view" onchange="this.form.requestSubmit()" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="all" @selected($filterView === 'all')>All accounts</option>
                                <option value="active" @selected($filterView === 'active')>Active</option>
                                <option value="inactive" @selected($filterView === 'inactive')>Inactive</option>
                                <option value="deleted" @selected($filterView === 'deleted')>Archived</option>
                            </select>
                        </div>
                        <div class="min-w-[220px] flex-1 xl:flex-none">
                            <label class="block text-xs font-medium text-slate-500">Search</label>
                            <input type="search" name="q" value="{{ $filterQ }}" placeholder="Name or email"
                                   data-live-search="#users-results" autocomplete="off"
                                   class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        </div>
                        <button
                            type="button"
                            x-data
                            x-on:click="$dispatch('open-modal', 'create-user')"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Add User
                        </button>
                    </form>
                </div>
                <x-scroll-list name="users" :records="$users" modal-target="#users-scroll-modals">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Name</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Email</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Role</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Account Status</th>
                                @if ($filterView === 'deleted')
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Archived</th>
                                @endif
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody data-scroll-rows class="divide-y divide-slate-100 bg-white">
@include('scroll-lists.users')
</tbody>
                    </table>
                </x-scroll-list>

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

            <div id="users-scroll-modals">@include('scroll-lists.users-modals')</div>
            </div>{{-- /#users-results --}}
        </div>
    </div>

    @include('partials.live-search')
</x-app-layout>
