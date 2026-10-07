@forelse ($records as $record)
    <div data-record-key="{{ $record->getKey() }}" class="p-4">
        @php
            $detailUrl = $kind !== 'visitors' || auth()->user()->isAdmin() ? route($kind . '.show', $record) : null;
            $label = $kind === 'incidents' ? $record->description : $record->full_name;
        @endphp
        @if ($detailUrl)
            <a href="{{ $detailUrl }}" class="font-semibold text-brand-700 underline">{{ $label }}</a>
        @else
            <p class="font-semibold text-slate-900">{{ $label }}</p>
        @endif
        <p class="mt-2 text-sm text-slate-600">
            @if ($kind === 'incidents')
                {{ $record->category ?: 'Uncategorized' }} · {{ \App\Enums\IncidentStatus::displayLabel($record->status) }} · {{ $record->reported_at?->format('M j, Y h:i A') }}
            @elseif ($kind === 'visitors')
                {{ $record->check_in?->format('M j, Y h:i A') }} · {{ $record->status }} · {{ $record->host_employee }} · {{ $record->house_address_or_unit }}
            @else
                {{ $record->relation_to_owner ?: 'Unspecified' }} · {{ $record->address_or_unit }}
                @include('residents.partials.phone-link', ['resident' => $record])
            @endif
        </p>
    </div>
@empty
    <p class="p-6 text-slate-500">No matching records.</p>
@endforelse
