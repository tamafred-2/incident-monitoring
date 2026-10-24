<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Visitor Profiles" subtitle="Register each visitor once, then add a visit whenever they return." />
    </x-slot>
    <div class="page-section"><div class="page-shell space-y-4">
        @include('partials.alerts')
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('expected-visits.index') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Expected Visits</a>
                @if (auth()->user()->isSecurity())
                    <button type="button" x-data @click="$dispatch('open-modal', 'add-visitor-profile')" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Add Visitor</button>
                @endif
            </div>
            <form method="GET" action="{{ route('visitor-profiles.index') }}" class="flex w-full flex-wrap items-end gap-3 lg:ml-auto lg:w-auto">
                @if (request('subdivision_id'))<input type="hidden" name="subdivision_id" value="{{ request('subdivision_id') }}">@endif
                <div>
                    <label for="profile-status" class="block text-xs font-medium text-slate-600">Status</label>
                    <select id="profile-status" name="status" onchange="this.form.requestSubmit()" class="mt-1 rounded-xl border-slate-300 text-sm shadow-sm">
                        <option value="">All visitors</option>
                        <option value="inside" @selected($status === 'inside')>Inside</option>
                        <option value="outside" @selected($status === 'outside')>Outside</option>
                    </select>
                </div>
                <input type="hidden" name="sort" value="{{ $sort }}">
                <div class="w-full sm:w-80">
                <label for="profile-search" class="sr-only">Search visitors</label>
                <input id="profile-search" name="q" value="{{ $q }}" type="search" placeholder="Search name or phone" data-live-search="#visitor-profile-results" autocomplete="off" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
            </form>
        </div>
        <div id="visitor-profile-results" class="space-y-4">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <x-scroll-list name="visitor-profiles" :records="$profiles">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-left text-slate-600"><tr>
                    @foreach (['Name' => ['name_asc', 'name_desc'], 'Phone' => null, 'Visits' => null, 'Status' => null, 'Created At' => ['oldest', 'newest'], 'Action' => null] as $heading => $directions)
                        <th class="px-6 py-3 font-semibold" @if ($directions) aria-sort="{{ $sort === $directions[0] ? 'ascending' : ($sort === $directions[1] ? 'descending' : 'none') }}" @endif>
                                @if ($directions)
                                    @php
                                        $ascending = $sort === $directions[0];
                                        $descending = $sort === $directions[1];
                                        $nextSort = $ascending ? $directions[1] : ($descending ? $directions[0] : ($heading === 'Created At' ? $directions[1] : $directions[0]));
                                        $nextAscending = $nextSort === $directions[0];
                                    @endphp
                                    <a href="{{ route('visitor-profiles.index', array_filter(['q' => $q, 'status' => $status, 'subdivision_id' => request('subdivision_id'), 'sort' => $nextSort], fn ($value) => $value !== null && $value !== '')) }}"
                                       aria-label="Sort {{ $heading }} {{ $nextAscending ? 'ascending' : 'descending' }}"
                                       title="{{ $heading === 'Name' ? ($nextAscending ? 'Sort A-Z' : 'Sort Z-A') : ($nextAscending ? 'Oldest first' : 'Newest first') }}"
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
                <tbody data-scroll-rows class="divide-y divide-slate-100">
                    @include('scroll-lists.visitor-profiles')
                </tbody>
            </table>
            </x-scroll-list>
        </div>
        </div>
        @if (auth()->user()->isSecurity())
            <x-modal name="add-visitor-profile" :show="$errors->any()" maxWidth="2xl" focusable>
                <form method="POST" action="{{ route('visitor-profiles.store') }}" enctype="multipart/form-data" class="space-y-5 bg-white p-6 sm:p-8">
                    @csrf
                    <input type="hidden" name="_submission_token" value="{{ bin2hex(random_bytes(16)) }}">
                    <h3 class="text-lg font-semibold text-slate-900">Add Visitor</h3>
                    <p class="text-sm text-slate-500">Save their identity and contact details. Visit details are recorded separately.</p>
                    @include('visitor-profiles.fields')
                    <div class="flex flex-wrap gap-3 pt-2"><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Save Visitor</button><button type="button" @click="$dispatch('close')" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button></div>
                </form>
            </x-modal>
        @endif
    </div></div>
    @include('partials.live-search')
</x-app-layout>
