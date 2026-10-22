<x-app-layout>
    <x-slot name="header"><x-page-header title="Expected Visits" subtitle="Visits arranged by resident phone calls. Check visitors in only when they arrive." /></x-slot>
    <div class="page-section"><div class="page-shell space-y-4">
        @include('partials.alerts')
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('visitor-profiles.index') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Choose or Create Visitor Profile</a>
            <a href="{{ route('visitors.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">Back to Gate</a>
        </div>
        <section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr>@foreach (['Visitor', 'Resident / House', 'Expected Arrival', 'Purpose / Call Notes', 'Recorded By', 'Actions'] as $heading)<th class="px-5 py-3">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($visits as $visit)
                    <tr>
                        <td class="px-5 py-4">{{ $visit->profile?->full_name ?? 'Unavailable profile' }}<div class="text-xs text-slate-500">{{ $visit->profile?->phone }}</div></td>
                        <td class="px-5 py-4">{{ $visit->resident?->full_name ?? 'Unavailable resident' }}<div class="text-xs text-slate-500">{{ $visit->resident?->house?->display_address }}</div></td>
                        <td class="whitespace-nowrap px-5 py-4">{{ $visit->expected_at->format('M j, Y h:i A') }}<div class="text-xs text-slate-500">{{ $visit->expected_at->isPast() ? 'Awaiting arrival - scheduled time passed' : 'Expected' }}</div></td>
                        <td class="max-w-xs whitespace-normal break-words px-5 py-4">{{ $visit->purpose ?: '?' }}</td>
                        <td class="px-5 py-4">{{ $visit->recorder?->full_name ?? 'Unknown' }}<div class="text-xs text-slate-500">{{ $visit->created_at->format('M j, Y h:i A') }}</div></td>
                        <td class="px-5 py-4">
                            @if (auth()->user()->hasRole('security'))
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('visitors.index', ['expected_visit' => $visit->getKey()]) }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Check In on Arrival</a>
                                <form method="POST" action="{{ route('expected-visits.cancel', $visit) }}">@csrf<button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold">Cancel Visit</button></form>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty<tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No expected visits. Open a visitor profile to schedule one.</td></tr>@endforelse
                </tbody>
            </table>
        </section>
        {{ $visits->links() }}
    </div></div>
</x-app-layout>
