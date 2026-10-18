@forelse ($records as $pendingIncident)
    <tr
        class="h-14 min-[1600px]:h-16 transition cursor-pointer hover:bg-slate-50"
        onclick="window.location='{{ route('incidents.show', ['incidentId' => $pendingIncident->incident_id]) }}'"
    >
        <td class="px-4 py-2 font-medium text-slate-700"><div class="max-w-[10rem] truncate" title="{{ $pendingIncident->category }}">{{ $pendingIncident->category ?: '-' }}</div></td>
        <td class="px-4 py-2 text-slate-600"><div class="max-w-[12rem] min-[1600px]:max-w-[26rem] truncate" title="{{ $pendingIncident->location }}">{{ $pendingIncident->location ?: '-' }}</div></td>
        <td class="px-4 py-2 text-slate-600">{{ \App\Enums\IncidentStatus::displayLabel($pendingIncident->status) }}</td>
        <td class="px-4 py-2 text-slate-600">
            @if ($pendingIncident->reported_at)
                <div class="min-w-[9rem]">
                    <div class="font-medium whitespace-nowrap text-slate-700">{{ $pendingIncident->reported_at->format('M j, Y') }}</div>
                    <div class="mt-1 text-xs min-[1600px]:text-sm whitespace-nowrap text-slate-500">{{ $pendingIncident->reported_at->format('h:i A') }}</div>
                </div>
            @else
                -
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="px-6 py-10 text-center text-slate-500">{{ trim((string) request('incident_q', '')) !== '' ? 'No pending incidents match your search.' : 'No pending incidents right now.' }}</td>
    </tr>
@endforelse
