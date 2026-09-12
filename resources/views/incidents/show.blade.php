<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-page-header title="Incident Details" subtitle="Full incident information with proof images and pending-to-resolved status tracking." />
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('incidents.index', $indexContext) }}"
                    class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                >
                    Back to Incidents
                </a>
                @if ($canEditIncident)
                    <a
                        href="{{ route('incidents.edit', array_merge(['incidentId' => $incident->incident_id], $indexContext)) }}"
                        class="px-4 py-2 text-sm font-semibold text-white transition rounded-xl bg-brand-600 hover:bg-brand-700"
                    >
                        Edit Incident
                    </a>
                @endif
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
                    this.previewLabel = label || 'Proof image preview';
                },
                closePreview() {
                    this.previewImage = null;
                    this.previewLabel = '';
                }
            }"
            class="flex flex-col max-w-6xl gap-6 px-4 mx-auto sm:px-6 lg:px-8"
        >
            @include('partials.alerts')
            @php
                $isResolvedIncident = in_array($incident->status, \App\Enums\IncidentStatus::resolvedValues(), true);
                $statusLabel = \App\Enums\IncidentStatus::displayLabel($incident->status);
            @endphp

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex flex-col gap-4 pb-5 border-b border-slate-200 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Incident</p>
                        <p class="mt-2 text-sm text-slate-500">
                            Subdivision: {{ $incident->subdivision->subdivision_name ?? '-' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $incident->trashed() ? 'bg-rose-100 text-rose-700' : ($isResolvedIncident ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ $incident->trashed() ? 'Archived' : $statusLabel }}
                        </span>
                        @if ($incident->trashed())
                            <span class="text-sm text-slate-500">Archived {{ optional($incident->deleted_at)->format('M j, Y h:i A') }}</span>
                        @endif
                    </div>
                </div>

                <div class="grid gap-6 mt-6 md:grid-cols-2">
                    <section class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Report Summary</h4>
                        <dl class="mt-4 space-y-4 text-sm">
                            @foreach (['Category' => $incident->category, 'Location' => ($incident->location ?: $incident->house?->display_address), 'Reported By' => $incident->reporter?->full_name] as $label => $value)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">{{ $label }}</dt>
                                    <dd class="max-w-[18rem] font-medium text-right text-slate-900 break-words">{{ $value ?: '-' }}</dd>
                                </div>
                            @endforeach
                            @if ($incident->house && $incident->location && strcasecmp(trim($incident->location), trim($incident->house->display_address)) !== 0)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">House / Unit</dt>
                                    <dd class="max-w-[18rem] font-medium text-right text-slate-900 break-words">{{ $incident->house->display_address }}</dd>
                                </div>
                            @endif
                        </dl>
                    </section>
                    <section class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Timeline</h4>
                        <p class="mt-2 text-xs text-slate-500">{{ config('app.timezone') }}</p>
                        @php
                            $sameDates = $incident->incident_date && $incident->reported_at && $incident->incident_date->equalTo($incident->reported_at);
                            $timeline = $sameDates
                                ? ['Incident / Reported Date' => $incident->reported_at]
                                : ['Incident Date' => $incident->incident_date, 'Date Reported' => $incident->reported_at];
                            $timeline['Date Resolved'] = $incident->resolved_at;
                        @endphp
                        <dl class="mt-4 space-y-4 text-sm">
                            @foreach ($timeline as $label => $date)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">{{ $label }}</dt>
                                    <dd class="font-medium text-right text-slate-900">
                                        @if ($date)
                                            <span class="block whitespace-nowrap">{{ $date->format('M j, Y') }}</span>
                                            <span class="mt-1 block whitespace-nowrap text-xs font-medium text-slate-500">{{ $date->format('h:i:s A') }}</span>
                                        @else
                                            <span class="text-slate-500">{{ $label === 'Date Resolved' ? 'Not resolved yet' : '-' }}</span>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                </div>
                <div class="p-5 mt-6 bg-white border rounded-2xl border-slate-200">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Description</h4>
                    <p class="mt-3 text-sm leading-7 whitespace-pre-line text-slate-700">{{ $incident->description ?: 'No description provided.' }}</p>
                </div>

                @include('incidents.partials.evidence-gallery')
            </div>
            <div x-cloak
                x-show="previewImage"
                x-on:keydown.escape.window="closePreview()"
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-slate-950/80"
                style="display: none;"
            >
                <div class="absolute inset-0" @click="closePreview()"></div>
                <div class="relative w-full max-w-5xl overflow-hidden bg-white shadow-2xl rounded-2xl">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                        <h3 class="text-base font-semibold text-slate-900" x-text="previewLabel || 'Proof image preview'"></h3>
                        <button
                            type="button"
                            @click="closePreview()"
                            class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                        >
                            Close
                        </button>
                    </div>
                    <div class="p-4 bg-slate-100">
                        <img :src="previewImage" :alt="previewLabel || 'Proof image preview'" class="max-h-[75vh] w-full rounded-2xl object-contain">
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
