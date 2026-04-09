<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Details</h2>
                <p class="mt-1 text-sm text-slate-500">Review account information without opening the edit modal.</p>
            </div>
            <a
                href="{{ route('users.index', $indexContext) }}"
                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Back to Users
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-5xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Account</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $managedUser->full_name }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $managedUser->email }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $managedUser->trashed() ? 'bg-rose-100 text-rose-700' : 'bg-sky-100 text-sky-700' }}">
                        {{ $managedUser->trashed() ? 'Archived' : ucfirst($managedUser->role) }}
                    </span>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Identity</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Surname</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $managedUser->surname ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">First Name</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $managedUser->first_name ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Middle Name</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $managedUser->middle_name ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Extension</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $managedUser->extension ?: '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Access</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Role</dt>
                                <dd class="text-right font-medium text-slate-900">{{ ucfirst($managedUser->role) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Linked Resident</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $managedUser->resident?->full_name ?? 'Not linked' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Subdivision</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $managedUser->role === 'admin' ? 'All' : ($managedUser->subdivision?->subdivision_name ?? 'Unassigned') }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Resident House</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $managedUser->resident?->house?->display_address ?? 'Not linked' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Archived</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $managedUser->deleted_at?->format('M j, Y h:i A') ?? 'No' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
