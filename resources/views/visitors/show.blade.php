<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visitor Details</h2>
                <p class="mt-1 text-sm text-slate-500">Full record for the selected checked-in or historical visitor entry.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('dashboard', $dashboardQuery) }}"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Back to Dashboard
                </a>
                <a
                    href="{{ route('visitors.index', auth()->user()->isAdmin() && $visitor->subdivision_id ? ['subdivision_id' => $visitor->subdivision_id] : []) }}"
                    class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
                >
                    Open Visitor List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-5xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Visitor</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $visitor->full_name }}</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            Subdivision: {{ $visitor->subdivision->subdivision_name ?? '-' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $visitor->status === 'Inside' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                            {{ $visitor->status }}
                        </span>

                        @if (auth()->user()->hasRole('security') && $visitor->status === 'Inside')
                            <form method="POST" action="{{ route('visitors.checkout', $visitor) }}">
                                @csrf
                                <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    Check Out
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Identity</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Surname</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $visitor->surname ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">First Name</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $visitor->first_name ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Middle Initials</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $visitor->middle_initials ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Extension</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $visitor->extension ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Phone</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $visitor->phone ?: '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visit Details</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Plate Number</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $visitor->plate_number ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Passenger Count</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $visitor->passenger_count ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Resident / Host</dt>
                                <dd class="max-w-[16rem] text-right font-medium text-slate-900 break-words">{{ $visitor->host_employee ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">House / Unit</dt>
                                <dd class="max-w-[16rem] text-right font-medium text-slate-900 break-words">{{ $visitor->house_address_or_unit ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">ID Photo</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $visitor->id_photo_path ? 'Uploaded' : 'Not uploaded' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Checked In</dt>
                                <dd class="text-right font-medium text-slate-900">
                                    @if ($visitor->check_in)
                                        <span class="block whitespace-nowrap">{{ $visitor->check_in->format('M j, Y') }}</span>
                                        <span class="mt-1 block whitespace-nowrap text-xs font-medium text-slate-500">{{ $visitor->check_in->format('h:i A') }}</span>
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Checked Out</dt>
                                <dd class="text-right font-medium text-slate-900">
                                    @if ($visitor->check_out)
                                        <span class="block whitespace-nowrap">{{ $visitor->check_out->format('M j, Y') }}</span>
                                        <span class="mt-1 block whitespace-nowrap text-xs font-medium text-slate-500">{{ $visitor->check_out->format('h:i A') }}</span>
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Purpose</h4>
                        <p class="mt-3 text-sm leading-7 text-slate-700 break-words">{{ $visitor->purpose ?: 'No purpose provided.' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visit Summary</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Subdivision</dt>
                                <dd class="max-w-[14rem] text-right font-medium text-slate-900 break-words">{{ $visitor->subdivision->subdivision_name ?? '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Status</dt>
                                <dd>
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $visitor->status === 'Inside' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                        {{ $visitor->status }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Has Vehicle</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $visitor->plate_number ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Record Type</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $visitor->host_employee ? 'Resident Visit' : 'General Visit' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
