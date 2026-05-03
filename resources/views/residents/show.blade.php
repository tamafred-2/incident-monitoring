<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Resident Details</h2>
                <p class="mt-1 text-sm text-slate-500">Review the resident profile and assigned house information.</p>
            </div>
            <a
                href="{{ route('residents.index', $indexContext) }}"
                class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
            >
                Back to Residents
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="flex flex-col max-w-5xl gap-6 px-4 mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex flex-col gap-4 pb-5 border-b border-slate-200 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Resident Profile</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $resident->full_name }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $resident->subdivision?->subdivision_name ?? '-' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $resident->status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                        {{ $resident->status }}
                    </span>
                </div>

                <div class="grid gap-6 mt-6 lg:grid-cols-2">
                    <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Resident Info</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Phone</dt>
                                <dd class="font-medium text-right text-slate-900">{{ $resident->phone ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Email</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-all">{{ $resident->email ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Created</dt>
                                <dd class="font-medium text-right text-slate-900">
                                    @if ($resident->created_at)
                                        <span class="block whitespace-nowrap">{{ $resident->created_at->format('M j, Y') }}</span>
                                        <span class="block mt-1 text-xs font-medium whitespace-nowrap text-slate-500">{{ $resident->created_at->format('h:i A') }}</span>
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Housing and Access</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Street</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-words">{{ $resident->house?->street ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Block</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-words">{{ $resident->house?->block ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Lot</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-words">{{ $resident->house?->lot ?: '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
