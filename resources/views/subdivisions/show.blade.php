<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Subdivision Details</h2>
                <p class="mt-1 text-sm text-slate-500">Review subdivision information and related record totals.</p>
            </div>
            <a
                href="{{ route('subdivisions.index', $indexContext) }}"
                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Back to Subdivisions
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-5xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Subdivision</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $subdivision->subdivision_name }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $subdivision->address ?: 'No address provided.' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $subdivision->status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                        {{ $subdivision->status }}
                    </span>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Profile</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Contact Person</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $subdivision->contact_person ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Contact Number</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $subdivision->contact_number ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Email</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $subdivision->email ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Archived</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $subdivision->deleted_at?->format('M j, Y h:i A') ?? 'No' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Related Records</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Users</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $subdivision->users_count }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Residents</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $subdivision->residents_count }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Visitors</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $subdivision->visitors_count }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Incidents</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $subdivision->incidents_count }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Houses</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $subdivision->houses_count }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
