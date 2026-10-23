@forelse ($profiles as $record)
    <tr data-record-key="{{ $record->getKey() }}">
        <td class="px-6 py-4 font-medium text-slate-900">{{ $record->full_name }}</td>
        <td class="px-6 py-4 text-slate-600">{{ $record->phone }}</td>
        <td class="px-6 py-4 text-slate-600">{{ $record->visits_count }}</td>
        <td class="px-6 py-4 text-slate-600">{{ $record->is_inside ? 'Currently inside' : 'Outside' }}</td>
        <td class="px-6 py-4 text-slate-600">
            <span class="block whitespace-nowrap">{{ $record->created_at?->format('M j, Y') ?: '-' }}</span>
            @if ($record->created_at)<span class="mt-1 block whitespace-nowrap text-xs text-slate-500">{{ $record->created_at->format('g:i A') }}</span>@endif
        </td>
        <td class="px-6 py-4">
            <a href="{{ route('visitor-profiles.show', $record) }}" class="inline-flex items-center whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900">View Profile</a>
        </td>
    </tr>
@empty
    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-500">No visitor profiles found.</td></tr>
@endforelse
