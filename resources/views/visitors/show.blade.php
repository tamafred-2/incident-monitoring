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
        <div
            x-data="{
                previewImage: null,
                previewLabel: '',
                openPreview(url, label) {
                    this.previewImage = url;
                    this.previewLabel = label || 'Visitor ID photo preview';
                },
                closePreview() {
                    this.previewImage = null;
                    this.previewLabel = '';
                }
            }"
            class="mx-auto flex max-w-5xl flex-col gap-6 px-4 sm:px-6 lg:px-8"
        >
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
                                <dd class="max-w-[16rem] text-right font-medium text-slate-900 break-words">{{ $displayHouseAddress ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">ID Photo</dt>
                                <dd class="text-right font-medium text-slate-900">
                                    @if ($visitor->id_photo_path)
                                        <img
                                            src="{{ route('visitors.photo', ['visitor' => $visitor->visitor_id]) }}"
                                            alt="Visitor ID photo for {{ $visitor->full_name }}"
                                            class="ml-auto h-20 w-20 cursor-zoom-in rounded-xl border border-slate-200 object-cover shadow-sm transition hover:opacity-90"
                                            @click="openPreview($el.src, @js('Visitor ID photo for ' . $visitor->full_name))"
                                            @keydown.enter.prevent="openPreview($el.src, @js('Visitor ID photo for ' . $visitor->full_name))"
                                            @keydown.space.prevent="openPreview($el.src, @js('Visitor ID photo for ' . $visitor->full_name))"
                                            role="button"
                                            tabindex="0"
                                        >
                                    @else
                                        Not uploaded
                                    @endif
                                </dd>
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

            <div
                x-cloak
                x-show="previewImage"
                x-on:keydown.escape.window="closePreview()"
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-slate-950/80"
                style="display: none;"
            >
                <div class="absolute inset-0" @click="closePreview()"></div>
                <div class="relative w-full max-w-4xl overflow-hidden bg-white shadow-2xl rounded-3xl">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                        <h3 class="text-base font-semibold text-slate-900" x-text="previewLabel || 'Visitor ID photo preview'"></h3>
                        <button
                            type="button"
                            @click="closePreview()"
                            class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                        >
                            Close
                        </button>
                    </div>
                    <div class="p-4 bg-slate-100">
                        <img :src="previewImage" :alt="previewLabel || 'Visitor ID photo preview'" class="max-h-[75vh] w-full rounded-2xl object-contain">
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
