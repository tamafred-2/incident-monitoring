<x-app-layout>
    <x-slot name="header"><x-page-header title="Expected Visits" subtitle="Visits arranged by resident phone calls. Check visitors in only when they arrive." /></x-slot>
    <div class="page-section"><div class="page-shell space-y-4">
        @include('partials.alerts')
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('visitor-profiles.index') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">{{ auth()->user()->isSecurity() ? 'Choose or Create Visitor Profile' : 'View Visitor Profiles' }}</a>
            <a href="{{ route('visitors.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">Back to Gate</a>
        </div>
        <section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr>
                    @foreach (['Visitor' => ['name_asc', 'name_desc'], 'Resident / House' => null, 'Expected Arrival' => null, 'Purpose / Call Notes' => null, 'Recorded By' => null, 'Created At' => ['oldest', 'newest'], 'Actions' => null] as $heading => $directions)
                        <th class="px-6 py-3 font-semibold" @if ($directions) aria-sort="{{ $sort === $directions[0] ? 'ascending' : ($sort === $directions[1] ? 'descending' : 'none') }}" @endif>
                                @if ($directions)
                                    @php
                                        $ascending = $sort === $directions[0];
                                        $descending = $sort === $directions[1];
                                        $nextSort = $ascending ? $directions[1] : ($descending ? $directions[0] : ($heading === 'Created At' ? $directions[1] : $directions[0]));
                                        $nextAscending = $nextSort === $directions[0];
                                    @endphp
                                    <a href="{{ route('expected-visits.index', array_merge(request()->except('page', 'sort'), ['sort' => $nextSort])) }}"
                                       aria-label="Sort {{ $heading }} {{ $nextAscending ? 'ascending' : 'descending' }}"
                                       title="{{ $heading === 'Visitor' ? ($nextAscending ? 'Sort A-Z' : 'Sort Z-A') : ($nextAscending ? 'Oldest first' : 'Newest first') }}"
                                       class="inline-flex items-center gap-1 whitespace-nowrap hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 {{ $ascending || $descending ? 'text-brand-700' : 'text-slate-600' }}">
                                        <span>{{ $heading }}</span>
                                        <span class="text-xs" aria-hidden="true">{!! $ascending ? '&uarr;' : ($descending ? '&darr;' : '&#8597;') !!}</span>
                                    </a>
                                @else
                                    {{ $heading }}
                                @endif
                        </th>
                    @endforeach
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($visits as $visit)
                    <tr>
                        <td class="px-5 py-4">{{ $visit->profile?->full_name ?? 'Unavailable profile' }}<div class="text-xs text-slate-500">{{ $visit->profile?->phone }}</div></td>
                        <td class="px-5 py-4">{{ $visit->resident?->full_name ?? 'Unavailable resident' }}<div class="text-xs text-slate-500">{{ $visit->resident?->house?->display_address }}</div></td>
                        <td class="whitespace-nowrap px-5 py-4">{{ $visit->expected_at->format('M j, Y h:i A') }}<div class="text-xs text-slate-500">{{ $visit->expected_at->isPast() ? 'Awaiting arrival - scheduled time passed' : 'Expected' }}</div></td>
                        <td class="max-w-xs whitespace-normal break-words px-5 py-4">{{ $visit->purpose ?: '?' }}</td>
                        <td class="px-5 py-4">{{ $visit->recorder?->full_name ?? 'Unknown' }}</td>
                        <td class="whitespace-nowrap px-5 py-4">{{ $visit->created_at->format('M j, Y h:i A') }}</td>
                        <td class="px-5 py-4">
                            @if (auth()->user()->hasRole('security'))
                            <div class="flex flex-wrap gap-2">
@if (auth()->user()->isSecurity())
                                <a href="{{ route('visitors.index', ['expected_visit' => $visit->getKey()]) }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Check In on Arrival</a>
@endif
                                <form method="POST" action="{{ route('expected-visits.cancel', $visit) }}">@csrf<button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold">Cancel Visit</button></form>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty<tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No expected visits. Open a visitor profile to schedule one.</td></tr>@endforelse
                </tbody>
            </table>
        </section>
        {{ $visits->links() }}
    </div></div>
</x-app-layout>
