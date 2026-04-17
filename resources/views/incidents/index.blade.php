<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Incidents</h2>
            <p class="mt-1 text-sm text-slate-500">Resident reports move from submission to admin assignment, on-site verification, status updates, and resident notifications.</p>
        </div>
    </x-slot>

    @php
        $activeIncidentTab = in_array($historyView, ['history', 'deleted', 'all'], true) ? 'history' : 'incident';
    @endphp

    <div class="py-10">
        <div
            x-data="{
                previewImage: null,
                previewLabel: '',
                activeIncidentTab: @js($activeIncidentTab),
                openPreview(url, label) {
                    this.previewImage = url;
                    this.previewLabel = label || 'Proof image preview';
                },
                closePreview() {
                    this.previewImage = null;
                    this.previewLabel = '';
                }
            }"
            class="flex flex-col gap-6 px-4 mx-auto max-w-7xl sm:px-6 lg:px-8"
        >
            @include('partials.alerts')

            @if ($subdivisions->isNotEmpty())
                <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <form method="GET" action="{{ route('incidents.index') }}" class="grid gap-4 md:grid-cols-[1fr_220px_auto] md:items-end">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Search</label>
                            <input
                                type="search"
                                name="q"
                                value="{{ $filterQ }}"
                                placeholder="Report ID, description, reporter, category, status"
                                class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                            <select name="subdivision_id" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                <option value="">All subdivisions</option>
                                @foreach ($subdivisions as $subdivision)
                                    <option value="{{ $subdivision->subdivision_id }}" @selected($filterSubdivision === $subdivision->subdivision_id)>{{ $subdivision->subdivision_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-wrap items-end gap-3 md:justify-end">
                            <input type="hidden" name="view" :value="activeIncidentTab === 'history' ? 'history' : 'active'">
                            <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-sky-600 hover:bg-sky-700">Apply</button>
                            <a
                                href="{{ route('incidents.index', array_filter([
                                    'view' => $activeIncidentTab === 'history' ? 'history' : null,
                                ])) }}"
                                class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                            >
                                Clear
                            </a>
                            @if (auth()->user()->hasRole(['security', 'staff', 'resident']))
                                <button
                                    type="button"
                                    x-data
                                    x-on:click="$dispatch('open-modal', 'report-incident')"
                                    class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800"
                                >
                                    Report Incident
                                </button>
                            @endif
                            @if (!auth()->user()->isResident())
                                <button
                                    type="button"
                                    id="open-report-scan"
                                    class="inline-flex items-center px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                                >
                                    Scan Report QR
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            @elseif (auth()->user()->hasRole(['security', 'staff', 'resident']))
                <div class="flex justify-end">
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'report-incident')"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800"
                    >
                        Report Incident
                    </button>
                </div>
            @endif

            <div class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200">
                <nav class="flex flex-wrap gap-6 px-2 pb-1 border-b border-slate-200" aria-label="Incident monitoring sections">
                    <a
                        href="{{ route('incidents.index', array_filter([
                            'q' => $filterQ ?: null,
                            'subdivision_id' => $filterSubdivision ?: null,
                        ])) }}"
                        :class="activeIncidentTab === 'incident' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-1 pb-3 text-sm font-semibold transition border-b-2"
                    >
                        Incident
                    </a>
                    <a
                        href="{{ route('incidents.index', array_filter([
                            'q' => $filterQ ?: null,
                            'subdivision_id' => $filterSubdivision ?: null,
                            'view' => 'history',
                        ])) }}"
                        :class="activeIncidentTab === 'history' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-1 pb-3 text-sm font-semibold transition border-b-2"
                    >
                        Incident History
                    </a>
                </nav>
            </div>

            <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                @if ($activeIncidentTab === 'history')
                    <div class="flex flex-col gap-4 px-6 py-4 border-b border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Incident History</h3>
                            <p class="mt-1 text-sm text-slate-500">Browse resolved incidents and older records.</p>
                        </div>

                        <form method="GET" action="{{ route('incidents.index') }}" class="flex flex-wrap items-end gap-3">
                            @if ($filterQ !== '')
                                <input type="hidden" name="q" value="{{ $filterQ }}">
                            @endif
                            @if ($filterSubdivision)
                                <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                            @endif
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">View</label>
                                <select
                                    name="view"
                                    class="mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                                >
                                    <option value="history" @selected($historyView === 'history')>Resolved</option>
                                    <option value="deleted" @selected($historyView === 'deleted')>Deleted</option>
                                    <option value="all" @selected($historyView === 'all')>All</option>
                                </select>
                            </div>

                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white transition rounded-xl bg-sky-600 hover:bg-sky-700"
                            >
                                Apply
                            </button>

                            <a
                                href="{{ route('incidents.index', array_filter([
                                    'q' => $filterQ ?: null,
                                    'subdivision_id' => $filterSubdivision ?: null,
                                    'view' => 'history',
                                ])) }}"
                                class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                            >
                                Clear
                            </a>
                        </form>
                    </div>
                @else
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900">Active Incidents</h3>
                        <p class="mt-1 text-sm text-slate-500">Incidents that are newly reported or still being handled.</p>
                    </div>
                @endif
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Report ID</th>
                                @if ($subdivisions->isNotEmpty())
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Subdivision</th>
                                @endif
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Category</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Status</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Verified Reporter</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Assigned Responder</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Proof</th>
                                @if ($historyView !== 'active')
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Archived At</th>
                                @endif
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Date Reported</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Date Resolved</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse ($incidents as $incident)
                                <tr>
                                    <td class="px-6 py-4">
                                        <a
                                            href="{{ route('incidents.show', array_filter([
                                                'incidentId' => $incident->incident_id,
                                                'q' => $filterQ ?: null,
                                                'subdivision_id' => $filterSubdivision ?: null,
                                                'view' => $historyView !== 'active' ? $historyView : null,
                                            ])) }}"
                                            class="font-mono font-medium text-slate-900 hover:text-sky-700"
                                        >
                                            {{ $incident->report_id }}
                                        </a>
                                    </td>
                                    @if ($subdivisions->isNotEmpty())
                                        <td class="px-6 py-4 text-slate-600">{{ $incident->subdivision->subdivision_name ?? '-' }}</td>
                                    @endif
                                    <td class="px-6 py-4 text-slate-600">{{ $incident->category ?: '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $incident->status }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $incident->verifiedResident?->full_name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $incident->assignedStaff?->full_name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600">
                                        @if ($incident->proofPhotos->isNotEmpty())
                                            @php($proofPhotoUrl = route('incidents.photos.show', ['path' => $incident->proofPhotos->first()->photo_path]))
                                            <button
                                                type="button"
                                                @click="openPreview('{{ $proofPhotoUrl }}', 'Proof image for {{ $incident->report_id }}')"
                                                class="relative block w-16 h-16 overflow-hidden border group rounded-xl border-slate-200 bg-slate-100"
                                                title="Preview proof images"
                                            >
                                                <img
                                                    src="{{ $proofPhotoUrl }}"
                                                    alt="Proof image for {{ $incident->report_id }}"
                                                    class="object-cover w-full h-full transition duration-200 group-hover:scale-105"
                                                >
                                                @if ($incident->proofPhotos->count() > 1)
                                                    <span class="absolute bottom-1 right-1 rounded-full bg-slate-900/80 px-2 py-0.5 text-[11px] font-semibold text-white">
                                                        +{{ $incident->proofPhotos->count() - 1 }}
                                                    </span>
                                                @endif
                                            </button>
                                        @elseif ($incident->proof_photo_path)
                                            @php($proofPhotoUrl = route('incidents.photos.show', ['path' => $incident->proof_photo_path]))
                                            <button
                                                type="button"
                                                @click="openPreview('{{ $proofPhotoUrl }}', 'Proof image for {{ $incident->report_id }}')"
                                                class="relative block w-16 h-16 overflow-hidden border group rounded-xl border-slate-200 bg-slate-100"
                                                title="Preview proof image"
                                            >
                                                <img
                                                    src="{{ $proofPhotoUrl }}"
                                                    alt="Proof image for {{ $incident->report_id }}"
                                                    class="object-cover w-full h-full transition duration-200 group-hover:scale-105"
                                                >
                                            </button>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    @if ($historyView !== 'active')
                                        <td class="px-6 py-4 text-slate-600">{{ optional($incident->deleted_at)->format('M j, Y H:i') ?: '-' }}</td>
                                    @endif
                                    <td class="px-6 py-4 text-slate-600">{{ optional($incident->reported_at)->format('M j, Y H:i') ?: '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ optional($incident->resolved_at)->format('M j, Y H:i') ?: '-' }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 whitespace-nowrap">
                                            <a
                                                href="{{ route('incidents.show', array_filter([
                                                    'incidentId' => $incident->incident_id,
                                                    'q' => $filterQ ?: null,
                                                    'subdivision_id' => $filterSubdivision ?: null,
                                                    'view' => $historyView !== 'active' ? $historyView : null,
                                                ])) }}"
                                                class="px-3 py-2 text-xs font-semibold border rounded-lg border-slate-200 text-slate-700 hover:bg-slate-50"
                                            >
                                                View
                                            </a>

                                            @if (auth()->user()->isAdmin())
                                                @if ($incident->trashed())
                                                    <form method="POST" action="{{ route('incidents.restore', $incident->incident_id) }}">
                                                        @csrf
                                                        <input type="hidden" name="q" value="{{ $filterQ }}">
                                                        <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                                        <input type="hidden" name="view" value="{{ $historyView }}">
                                                        <button class="px-3 py-2 text-xs font-semibold border rounded-lg border-emerald-200 text-emerald-700 hover:bg-emerald-50">Restore</button>
                                                    </form>
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="$dispatch('open-modal', 'force-delete-incident-{{ $incident->incident_id }}')"
                                                        class="px-3 py-2 text-xs font-semibold border rounded-lg border-rose-200 text-rose-700 hover:bg-rose-50"
                                                    >
                                                        Force Delete
                                                    </button>
                                                @else
                                                    <a
                                                        href="{{ route('incidents.edit', array_filter([
                                                            'incidentId' => $incident->incident_id,
                                                            'q' => $filterQ ?: null,
                                                            'subdivision_id' => $filterSubdivision ?: null,
                                                            'view' => $historyView !== 'active' ? $historyView : null,
                                                        ])) }}"
                                                        class="px-3 py-2 text-xs font-semibold border rounded-lg border-sky-200 text-sky-700 hover:bg-sky-50"
                                                    >
                                                        Edit
                                                    </a>
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="$dispatch('open-modal', 'archive-incident-{{ $incident->incident_id }}')"
                                                        class="px-3 py-2 text-xs font-semibold border rounded-lg border-rose-200 text-rose-700 hover:bg-rose-50"
                                                    >
                                                        Archive
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $subdivisions->isNotEmpty() ? ($historyView !== 'active' ? 11 : 10) : ($historyView !== 'active' ? 10 : 9) }}" class="px-6 py-10 text-center text-slate-500">No incidents found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (auth()->user()->hasRole(['security', 'staff', 'resident']))
                @include('incidents.partials.report-modal')
            @endif

            @if (!auth()->user()->isResident())
                <div id="report_scan_modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/70 px-4">
                    <div class="w-full max-w-lg p-6 bg-white shadow-2xl rounded-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900">Scan incident report QR</h3>
                            <button type="button" id="close-report-scan" class="px-2 py-1 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700">&times;</button>
                        </div>
                        <div id="report_qr_reader" class="min-h-[260px]"></div>
                        <p id="report_scan_status" class="mt-3 text-sm text-slate-500"></p>
                    </div>
                </div>
            @endif

            @if (auth()->user()->isAdmin())
                @foreach ($incidents as $incident)
                    @if (!$incident->trashed())
                        <x-modal name="archive-incident-{{ $incident->incident_id }}" maxWidth="md" focusable>
                            <div class="p-6 bg-white sm:p-8">
                                <div class="flex items-start gap-4">
                                    <div class="flex items-center justify-center w-12 h-12 shrink-0 rounded-2xl bg-rose-100 text-rose-600">
                                        <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">Archive Incident?</h3>
                                        <p class="mt-2 text-sm text-slate-600">
                                            {{ $incident->report_id }} will be removed from the active list, but it will stay available in the deleted view and can still be restored later.
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('incidents.destroy', $incident->incident_id) }}" class="flex flex-wrap justify-end gap-3 mt-6">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="q" value="{{ $filterQ }}">
                                    <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                    <input type="hidden" name="view" value="{{ $historyView }}">

                                    <button
                                        type="button"
                                        x-on:click="$dispatch('close')"
                                        class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                                    >
                                        Cancel
                                    </button>
                                    <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-rose-600 hover:bg-rose-700">
                                        Archive Incident
                                    </button>
                                </form>
                            </div>
                        </x-modal>
                    @else
                        <x-modal name="force-delete-incident-{{ $incident->incident_id }}" maxWidth="md" focusable>
                            <div class="p-6 bg-white sm:p-8">
                                <div class="flex items-start gap-4">
                                    <div class="flex items-center justify-center w-12 h-12 shrink-0 rounded-2xl bg-rose-100 text-rose-700">
                                        <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">Permanently Delete Incident?</h3>
                                        <p class="mt-2 text-sm text-slate-600">
                                            This will permanently remove {{ $incident->report_id }} and its proof images. This action cannot be undone.
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('incidents.force-delete', $incident->incident_id) }}" class="flex flex-wrap justify-end gap-3 mt-6">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="q" value="{{ $filterQ }}">
                                    <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                    <input type="hidden" name="view" value="{{ $historyView }}">

                                    <button
                                        type="button"
                                        x-on:click="$dispatch('close')"
                                        class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                                    >
                                        Cancel
                                    </button>
                                    <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-rose-700 hover:bg-rose-800">
                                        Force Delete
                                    </button>
                                </form>
                            </div>
                        </x-modal>
                    @endif
                @endforeach
            @endif

            <div
                x-cloak
                x-show="previewImage"
                x-on:keydown.escape.window="closePreview()"
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-slate-950/80"
                style="display: none;"
            >
                <div class="absolute inset-0" @click="closePreview()"></div>
                <div class="relative w-full max-w-5xl overflow-hidden bg-white shadow-2xl rounded-3xl">
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
    @if (!auth()->user()->isResident())
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script>
            (function () {
                var openBtn = document.getElementById('open-report-scan');
                var closeBtn = document.getElementById('close-report-scan');
                var modal = document.getElementById('report_scan_modal');
                var statusEl = document.getElementById('report_scan_status');
                var scanner = null;

                if (!openBtn || !closeBtn || !modal || !statusEl) {
                    return;
                }

                function stopScanner() {
                    if (!scanner) {
                        return Promise.resolve();
                    }

                    return scanner.stop().catch(function () {}).then(function () {
                        scanner = null;
                        var reader = document.getElementById('report_qr_reader');
                        if (reader) {
                            reader.innerHTML = '';
                        }
                    });
                }

                openBtn.addEventListener('click', function () {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    statusEl.textContent = 'Starting camera...';

                    if (typeof Html5Qrcode === 'undefined') {
                        statusEl.textContent = 'QR scanner failed to load.';
                        return;
                    }

                    scanner = new Html5Qrcode('report_qr_reader');
                    Html5Qrcode.getCameras()
                        .then(function (cameras) {
                            if (!cameras || !cameras.length) {
                                throw new Error('No camera found');
                            }

                            var cameraId = cameras[0].id;
                            return scanner.start(
                                cameraId,
                                { fps: 10, qrbox: { width: 250, height: 250 } },
                                function (decodedText) {
                                    var reportId = decodedText.indexOf('INCIDENT:') === 0 ? decodedText.slice(9) : decodedText;
                                    stopScanner().then(function () {
                                        window.location.href = @json(url('/incidents/report')) + '/' + encodeURIComponent(reportId);
                                    });
                                },
                                function () {}
                            );
                        })
                        .then(function () {
                            statusEl.textContent = 'Point the camera at the incident report QR code.';
                        })
                        .catch(function () {
                            statusEl.textContent = 'Could not start the camera.';
                        });
                });

                closeBtn.addEventListener('click', function () {
                    stopScanner().then(function () {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    });
                });
            })();
        </script>
    @endif
</x-app-layout>
