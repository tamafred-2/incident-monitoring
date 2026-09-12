<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="ucfirst($kind) . ' matching chart selection'" :subtitle="$kind === 'residents' ? 'Current directory' : $from . ' to ' . $to" />
    </x-slot>
    <div class="mx-auto max-w-7xl space-y-4 px-4 py-6">
        <a href="{{ route('analytics.index', ['from' => request('analytics_from', $from), 'to' => request('analytics_to', $to), 'granularity' => request('granularity', 'daily')]) }}" class="font-semibold text-brand-700 underline">Back to analytics</a>
        <p class="text-sm text-slate-600">{{ $records->total() }} matching records @if ($selection) · {{ request('chart') === 'topHouses' ? 'House #' . $selection : $selection }} @endif</p>
        <div class="divide-y rounded-2xl border border-slate-200 bg-white">
            @forelse ($records as $record)
                <div class="p-4">
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
        </div>
        {{ $records->links() }}
    </div>
</x-app-layout>
