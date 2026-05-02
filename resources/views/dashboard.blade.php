<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
            <p class="mt-1 text-sm text-slate-500">Overview of visitor approvals, resident response tracking, and incident monitoring activity.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @include('partials.alerts')

            @if ($isResidentDashboard)
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                        <p class="text-sm text-slate-500">Your Subdivision</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalSubdivisions }}</p>
                    </div>
                    <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                        <p class="text-sm text-slate-500">Your Complaints</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalIncidents }}</p>
                    </div>
                    <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                        <p class="text-sm text-slate-500">Pending Complaints</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $residentOpenIncidents }}</p>
                    </div>
                    <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                        <p class="text-sm text-slate-500">Resolved Complaints</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $residentResolvedIncidents }}</p>
                    </div>
                </div>

                <div class="p-6 mt-8 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900">Resident Overview</h3>
                    <p class="mt-2 text-sm text-slate-500">Use the Incidents page to report incidents and track pending or resolved reports tied to your account.</p>
                    <div class="grid gap-4 mt-6 md:grid-cols-2">
                        <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                            <p class="text-sm text-slate-500">Linked Resident Record</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ auth()->user()->resident?->full_name ?? 'Not linked' }}</p>
                        </div>
                        <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                            <p class="text-sm text-slate-500">Linked House</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ auth()->user()->resident?->house?->display_address ?? 'Not assigned' }}</p>
                        </div>
                    </div>
                </div>
            @else
                @php
                    $canViewVisitorMonitoring = auth()->user()->isAdmin() || auth()->user()->role === 'security';
                @endphp
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                        <p class="text-sm text-slate-500">{{ $isStaffDashboard ? 'Active Incidents' : 'Total Incidents' }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $isStaffDashboard ? $staffActiveIncidents : $totalIncidents }}</p>
                    </div>
                    <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                        <p class="text-sm text-slate-500">Total Residents</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalResidents }}</p>
                    </div>
                    @if ($isStaffDashboard)
                        <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                            <p class="text-sm text-slate-500">Pending Incidents</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $staffPendingIncidents }}</p>
                        </div>
                    @endif
                    <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                        <p class="text-sm text-slate-500">Total Houses</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalHouses }}</p>
                    </div>
                    @if ($canViewVisitorMonitoring)
                        <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                            <p class="text-sm text-slate-500">Visitors Today</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $visitorsToday }}</p>
                        </div>
                        <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                            <p class="text-sm text-slate-500">Visitors Inside</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $visitorsInside }}</p>
                        </div>
                    @endif
                </div>

                @php
                    $usesIncidentVisitorSplitLayout = $showPendingIncidentList && $canViewVisitorMonitoring;
                @endphp
                <div class="{{ $usesIncidentVisitorSplitLayout ? 'mt-8 grid gap-6 xl:grid-cols-[3fr_2fr] xl:items-start' : 'mt-8' }}">
                @if ($showPendingIncidentList)
                    <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                            <div class="flex flex-col gap-4 px-6 py-4 border-b border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Pending Incidents</h3>
                                    <p class="mt-1 text-sm text-slate-500">Active incident cases that still need handling.</p>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-6 py-3 font-semibold text-left text-slate-600">Report ID</th>
                                            <th class="px-6 py-3 font-semibold text-left text-slate-600">Category</th>
                                            <th class="px-6 py-3 font-semibold text-left text-slate-600">Location</th>
                                            <th class="px-6 py-3 font-semibold text-left text-slate-600">Status</th>
                                            <th class="px-6 py-3 font-semibold text-left text-slate-600">Reported</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        @forelse ($dashboardPendingIncidentList as $pendingIncident)
                                            <tr>
                                                <td class="px-6 py-4">
                                                    <a
                                                        href="{{ route('incidents.show', ['incidentId' => $pendingIncident->incident_id]) }}"
                                                        class="font-mono font-medium text-sky-700 hover:text-sky-900"
                                                    >
                                                        {{ $pendingIncident->report_id }}
                                                    </a>
                                                </td>
                                                <td class="px-6 py-4 text-slate-700">{{ $pendingIncident->category ?: '-' }}</td>
                                                <td class="px-6 py-4 text-slate-600">{{ $pendingIncident->location ?: '-' }}</td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">
                                                        {{ $pendingIncident->status }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-slate-600">
                                                    @if ($pendingIncident->reported_at)
                                                        <div class="min-w-[9rem]">
                                                            <div class="font-medium whitespace-nowrap text-slate-700">{{ $pendingIncident->reported_at->format('M j, Y') }}</div>
                                                            <div class="mt-1 text-xs whitespace-nowrap text-slate-500">{{ $pendingIncident->reported_at->format('h:i A') }}</div>
                                                        </div>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-6 py-10 text-center text-slate-500">No pending incidents right now.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex flex-col gap-3 px-6 py-4 border-t border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-slate-500">
                                    @if ($dashboardPendingIncidentList->total() > 0)
                                        Showing {{ $dashboardPendingIncidentList->firstItem() }}-{{ $dashboardPendingIncidentList->lastItem() }} of {{ $dashboardPendingIncidentList->total() }}
                                    @else
                                        No pending incident records to paginate
                                    @endif
                                </p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                                        <label for="pending-incidents-rows-per-page" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Rows</label>
                                        <input
                                            id="pending-incidents-rows-per-page"
                                            type="text"
                                            name="pending_incidents_per_page"
                                            list="pending-incidents-row-size-options"
                                            value=""
                                            placeholder="{{ $pendingIncidentsPerPage }}"
                                            class="w-24 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                                            aria-label="Rows per page"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            pattern="[0-9]{1,3}"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3)"
                                            onchange="if (this.value.trim() !== '') { this.form.requestSubmit(); }"
                                            onkeydown="if (event.key === 'Enter') { event.preventDefault(); if (this.value.trim() !== '') { this.form.requestSubmit(); } }"
                                        >
                                        <datalist id="pending-incidents-row-size-options">
                                            <option value="10"></option>
                                            <option value="25"></option>
                                            <option value="50"></option>
                                            <option value="100"></option>
                                        </datalist>
                                    </form>
                                    @if ($dashboardPendingIncidentList->onFirstPage())
                                        <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></span>
                                    @else
                                        <a href="{{ $dashboardPendingIncidentList->previousPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></a>
                                    @endif
                                    <span class="px-3 py-2 text-sm font-semibold rounded-xl bg-slate-100 text-slate-700" aria-label="Current page">
                                        {{ $dashboardPendingIncidentList->currentPage() }}
                                    </span>
                                    @if ($dashboardPendingIncidentList->hasMorePages())
                                        <a href="{{ $dashboardPendingIncidentList->nextPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></a>
                                    @else
                                        <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></span>
                                    @endif
                                </div>
                            </div>
                    </div>
                @endif

                @if ($canViewVisitorMonitoring)
                    <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                    <div class="flex flex-col gap-4 px-6 py-4 border-b border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Visitors Currently Checked In</h3>
                            <p class="mt-1 text-sm text-slate-500">Compact live list. Click a visitor name to open the full details.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Subdivision</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Visitor</th>
                                </tr>
                            </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse ($insideVisitors as $visitor)
                                <tr
                                    class="transition cursor-pointer hover:bg-slate-50 focus-within:bg-slate-50"
                                    role="link"
                                    tabindex="0"
                                    onclick="window.location='{{ route('visitors.show', array_merge(['visitor' => $visitor], request()->only(['inside_per_page', 'page']))) }}'"
                                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location='{{ route('visitors.show', array_merge(['visitor' => $visitor], request()->only(['inside_per_page', 'page']))) }}'; }"
                                >
                                    <td class="px-6 py-4 text-slate-600">
                                        <div class="max-w-[14rem] truncate" title="{{ $visitor->subdivision->subdivision_name ?? '-' }}">
                                            {{ $visitor->subdivision->subdivision_name ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="min-w-[12rem]">
                                            <div class="font-medium text-sky-700">{{ $visitor->full_name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                @if ($visitor->check_in)
                                                    {{ $visitor->check_in->format('M j, Y') }} at {{ $visitor->check_in->format('h:i A') }}
                                                @else
                                                    No check-in time
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                    <tr>
                                        <td colspan="2" class="px-6 py-10 text-center text-slate-500">
                                            No visitors are currently checked in.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col gap-3 px-6 py-4 border-t border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-500">
                            @if ($insideVisitors->total() > 0)
                                Showing {{ $insideVisitors->firstItem() }}-{{ $insideVisitors->lastItem() }} of {{ $insideVisitors->total() }}
                            @else
                                No visitor records to paginate
                            @endif
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                                <label for="dashboard-rows-per-page" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Rows</label>
                                <input
                                    id="dashboard-rows-per-page"
                                    type="text"
                                    name="inside_per_page"
                                    list="dashboard-row-size-options"
                                    value=""
                                    placeholder="{{ $insidePerPage }}"
                                    class="w-24 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                                    aria-label="Rows per page"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    pattern="[0-9]{1,3}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3)"
                                    onchange="if (this.value.trim() !== '') { this.form.requestSubmit(); }"
                                    onkeydown="if (event.key === 'Enter') { event.preventDefault(); if (this.value.trim() !== '') { this.form.requestSubmit(); } }"
                                >
                                <datalist id="dashboard-row-size-options">
                                    <option value="10"></option>
                                    <option value="25"></option>
                                    <option value="50"></option>
                                    <option value="100"></option>
                                </datalist>
                            </form>
                            @if ($insideVisitors->onFirstPage())
                                <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></span>
                            @else
                                <a href="{{ $insideVisitors->previousPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></a>
                            @endif
                            <span class="px-3 py-2 text-sm font-semibold rounded-xl bg-slate-100 text-slate-700" aria-label="Current page">
                                {{ $insideVisitors->currentPage() }}
                            </span>
                            @if ($insideVisitors->hasMorePages())
                                <a href="{{ $insideVisitors->nextPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></a>
                            @else
                                <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></span>
                            @endif
                        </div>
                    </div>
                    </div>
                @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
