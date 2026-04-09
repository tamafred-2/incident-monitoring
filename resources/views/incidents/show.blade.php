<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Incident Details</h2>
                <p class="mt-1 text-sm text-slate-500">Full incident information, verification details, and proof images in one place.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('incidents.index', $indexContext) }}"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Back to Incidents
                </a>
                @if (auth()->user()->isAdmin() && ! $incident->trashed())
                    <a
                        href="{{ route('incidents.edit', array_merge(['incidentId' => $incident->incident_id], $indexContext)) }}"
                        class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
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
            class="mx-auto flex max-w-6xl flex-col gap-6 px-4 sm:px-6 lg:px-8"
        >
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Incident</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $incident->title }}</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            Subdivision: {{ $incident->subdivision->subdivision_name ?? '-' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $incident->trashed() ? 'bg-rose-100 text-rose-700' : 'bg-sky-100 text-sky-700' }}">
                            {{ $incident->trashed() ? 'Archived' : $incident->status }}
                        </span>
                        @if ($incident->trashed())
                            <span class="text-sm text-slate-500">Archived {{ optional($incident->deleted_at)->format('M j, Y h:i A') }}</span>
                        @endif
                    </div>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Report Summary</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            @php
                                $incidentDate = $incident->incident_date;
                                $reportedAt = $incident->reported_at;
                                $sameIncidentAndReportedDate = $incidentDate && $reportedAt
                                    ? $incidentDate->equalTo($reportedAt)
                                    : false;
                            @endphp
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Category</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $incident->category ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Location</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $incident->location ?: '-' }}</dd>
                            </div>
                            @if ($sameIncidentAndReportedDate)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Incident / Reported Date</dt>
                                    <dd class="text-right font-medium text-slate-900">{{ $incidentDate->format('M j, Y h:i A') }}</dd>
                                </div>
                            @else
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Incident Date</dt>
                                    <dd class="text-right font-medium text-slate-900">{{ optional($incident->incident_date)->format('M j, Y h:i A') ?: '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Date Reported</dt>
                                    <dd class="text-right font-medium text-slate-900">{{ optional($incident->reported_at)->format('M j, Y h:i A') ?: '-' }}</dd>
                                </div>
                            @endif
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Date Resolved</dt>
                                <dd class="text-right font-medium text-slate-900">{{ optional($incident->resolved_at)->format('M j, Y h:i A') ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Reported By</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $incident->reporter?->full_name ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Verification</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Verified Reporter</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $incident->verifiedResident?->full_name ?? '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Method</dt>
                                <dd class="text-right font-medium text-slate-900">{{ $incident->verification_method ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Verified At</dt>
                                <dd class="text-right font-medium text-slate-900">{{ optional($incident->verified_at)->format('M j, Y h:i A') ?: '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Description</h4>
                    <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $incident->description ?: 'No description provided.' }}</p>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Proof Images</h4>
                    @if ($proofPhotos->isNotEmpty())
                        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($proofPhotos as $photo)
                                <button
                                    type="button"
                                    @click="openPreview('{{ $photo['url'] }}', 'Proof image {{ $loop->iteration }} for {{ addslashes($incident->title) }}')"
                                    class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 transition hover:-translate-y-0.5 hover:shadow-md"
                                >
                                    <img
                                        src="{{ $photo['url'] }}"
                                        alt="Proof image {{ $loop->iteration }} for {{ $incident->title }}"
                                        class="h-56 w-full object-cover"
                                    >
                                    <div class="px-4 py-3 text-sm font-medium text-slate-700">
                                        Proof image {{ $loop->iteration }}
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-500">No proof images were attached to this incident.</p>
                    @endif
                </div>
            </div>

            <div
                x-cloak
                x-show="previewImage"
                x-on:keydown.escape.window="closePreview()"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6"
                style="display: none;"
            >
                <div class="absolute inset-0" @click="closePreview()"></div>
                <div class="relative w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h3 class="text-base font-semibold text-slate-900" x-text="previewLabel || 'Proof image preview'"></h3>
                        <button
                            type="button"
                            @click="closePreview()"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Close
                        </button>
                    </div>
                    <div class="bg-slate-100 p-4">
                        <img :src="previewImage" :alt="previewLabel || 'Proof image preview'" class="max-h-[75vh] w-full rounded-2xl object-contain">
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
