<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="page-title">User Details</h2>
                <p class="page-subtitle">Review account information without opening the edit modal.</p>
            </div>
            <a
                href="{{ route('users.index', $indexContext) }}"
                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Back to Users
            </a>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="page-shell flex flex-col gap-4 min-[1600px]:gap-6">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Account</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $managedUser->full_name }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $managedUser->email }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $managedUser->trashed() ? 'bg-rose-100 text-rose-700' : 'bg-brand-100 text-brand-700' }}">
                        {{ $managedUser->trashed() ? 'Archived' : $managedUser->role?->label() }}
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
                                <dd class="text-right font-medium text-slate-900">{{ $managedUser->role?->label() }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Subdivision</dt>
                                <dd class="max-w-[16rem] text-right font-medium text-slate-900 break-words">{{ $managedUser->isAdmin() ? 'All' : ($managedUser->subdivision?->subdivision_name ?? 'Unassigned') }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Account Status</dt>
                                <dd class="text-right font-medium {{ $managedUser->is_active ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $managedUser->is_active ? 'Active' : 'Inactive' }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Archived</dt>
                                <dd class="text-right font-medium text-slate-900">
                                    @if ($managedUser->deleted_at)
                                        <span class="block whitespace-nowrap">{{ $managedUser->deleted_at->format('M j, Y') }}</span>
                                        <span class="mt-1 block whitespace-nowrap text-xs font-medium text-slate-500">{{ $managedUser->deleted_at->format('h:i A') }}</span>
                                    @else
                                        No
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
