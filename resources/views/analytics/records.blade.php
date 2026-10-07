<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="ucfirst($kind) . ' matching chart selection'" :subtitle="$kind === 'residents' ? 'Current directory' : $from . ' to ' . $to" />
    </x-slot>
    <div class="page-shell page-section space-y-4">
        <a href="{{ route('analytics.index', ['from' => request('analytics_from', $from), 'to' => request('analytics_to', $to), 'granularity' => request('granularity', 'daily')]) }}" class="font-semibold text-brand-700 underline">Back to analytics</a>
        <p class="text-sm text-slate-600">{{ $records->total() }} matching records @if ($selection) · {{ request('chart') === 'topHouses' ? 'House #' . $selection : $selection }} @endif</p>
<x-scroll-list name="analytics-records" :records="$records"><div data-scroll-rows class="divide-y rounded-2xl border border-slate-200 bg-white">
@include('scroll-lists.analytics-records')
</div></x-scroll-list>

    </div>
</x-app-layout>
