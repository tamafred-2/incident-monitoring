@php
    $isResidentViewer = auth()->user()->isResident();
    $activeIncidentTab = $historyView === 'history' ? 'history' : 'incident';
    $sharedFilters = array_filter([
        ...request()->only(['chart_filter', 'category', 'status', 'house_id']),
        'date_from' => $filterDateFrom ?: null,
        'date_to' => $filterDateTo ?: null,
    ]);
    $emptyStateColspan = ($isResidentViewer ? 0 : 1) + ($activeIncidentTab === 'history' ? 5 : 4);
@endphp
@forelse ($incidents as $incident)
    <tr data-record-key="{{ $incident->getKey() }}">
        <td class="px-6 py-4 text-slate-600">
            <div class="min-w-[11rem]">
                <a
                    href="{{ route('incidents.show', array_filter([
                        'incidentId' => $incident->incident_id,
                        'q' => $filterQ ?: null,
                        'subdivision_id' => $filterSubdivision ?: null,
                        'view' => $historyView !== 'active' ? $historyView : null,
                        'per_page' => $perPage,
                        ...$sharedFilters,
                    ])) }}"
                    class="font-medium text-slate-900 hover:text-brand-700"
                >
                    {{ $incident->category ?: 'Uncategorized' }}
                </a>
                <div class="mt-1 max-w-[14rem] truncate text-xs text-slate-500" title="{{ $incident->location ?: 'No location provided' }}">
                    {{ $incident->location ?: 'No location provided' }}
                </div>
            </div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            @php
                $isResolvedIncident = in_array($incident->status, \App\Enums\IncidentStatus::resolvedValues(), true);
                $statusLabel = \App\Enums\IncidentStatus::displayLabel($incident->status);
            @endphp
            {{ $incident->trashed() ? 'Archived' : $statusLabel }}
        </td>
        @unless ($isResidentViewer)
            <td class="px-6 py-4 text-slate-600">
                <div class="max-w-[13rem] truncate" title="{{ $incident->reporter?->full_name ?? '-' }}">
                    {{ $incident->reporter?->full_name ?? '-' }}
                </div>
            </td>
        @endunless
        <td class="px-6 py-4 text-slate-600">
            @if ($incident->reported_at)
                <div class="min-w-[9rem]">
                    <div class="whitespace-nowrap font-medium text-slate-700">{{ $incident->reported_at->format('M j, Y') }}</div>
                    <div class="mt-1 whitespace-nowrap text-xs text-slate-500">{{ $incident->reported_at->format('h:i A') }}</div>
                </div>
            @else
                -
            @endif
        </td>
        @if ($activeIncidentTab === 'history')
            <td class="px-6 py-4 text-slate-600">
                @if ($incident->resolved_at)
                    <div class="min-w-[9rem]">
                        <div class="whitespace-nowrap font-medium text-slate-700">{{ $incident->resolved_at->format('M j, Y') }}</div>
                        <div class="mt-1 whitespace-nowrap text-xs text-slate-500">{{ $incident->resolved_at->format('h:i A') }}</div>
                    </div>
                @else
                    -
                @endif
            </td>
        @endif
        <td class="px-6 py-4">
            <div class="flex items-center gap-2 whitespace-nowrap">
                <a
                    href="{{ route('incidents.show', array_filter([
                    'incidentId' => $incident->incident_id,
                    'q' => $filterQ ?: null,
                    'subdivision_id' => $filterSubdivision ?: null,
                    'view' => $historyView !== 'active' ? $historyView : null,
                    'per_page' => $perPage,
                    ...$sharedFilters,
                ])) }}"
                    class="px-3 py-2 text-xs font-semibold border rounded-lg border-slate-200 text-slate-700 hover:bg-slate-50"
                >
                    View
                </a>

                @if ((auth()->user()->isAdmin() || (auth()->user()->hasRole(['staff']) && !$incident->isResolvedOrDone())) && !$incident->trashed())
                    <a
                        href="{{ route('incidents.edit', array_filter([
                            'incidentId' => $incident->incident_id,
                            'q' => $filterQ ?: null,
                            'subdivision_id' => $filterSubdivision ?: null,
                            'view' => $historyView !== 'active' ? $historyView : null,
                            'per_page' => $perPage,
                            ...$sharedFilters,
                        ])) }}"
                        class="px-3 py-2 text-xs font-semibold border rounded-lg border-brand-200 text-brand-700 hover:bg-brand-50"
                    >
                        Edit
                    </a>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ $emptyStateColspan }}" class="px-6 py-10 text-center text-slate-500">No incidents found.</td>
    </tr>
@endforelse
