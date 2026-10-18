<x-app-layout>
    <x-slot name="header">
        <x-page-header :compact="true" title="Dashboard" subtitle="Overview of visitor approvals, resident response tracking, and incident monitoring activity." />
    </x-slot>

    <div class="page-section">
        <div class="page-shell">
            @include('partials.alerts')

                @php
                    $canViewVisitorMonitoring = auth()->user()->isAdmin() || auth()->user()->isSecurity();
                @endphp
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    <x-stat-card :compact="true" :href="route('incidents.index', ['view' => 'all'])" label="Total Incidents" tone="rose" :tinted="true" :value="$summary['total_incidents']" :hint="$summary['pending_incidents'] . ' pending'" />
                    <x-stat-card :compact="true" :href="route('visitors.index', ['tab' => 'history', 'chart_filter' => 1, 'date_from' => now()->startOfDay()->format('Y-m-d\TH:i'), 'date_to' => now()->endOfDay()->format('Y-m-d\TH:i')])" label="Today's Visitors" tone="brand" :tinted="true" :value="$visitorsToday" :hint="$summary['visitors_inside'] . ' currently inside'" />
                    <x-stat-card :compact="true" :href="route('residents.index')" label="Total Residents" tone="violet" :tinted="true" :value="$summary['total_residents']" />
                    <x-stat-card :compact="true" :href="auth()->user()->isAdmin() ? route('houses.index') : route('residents.index')" label="Total Houses" tone="teal" :tinted="true" :value="$summary['total_houses']" />
                </div>

                @php
                    $usesIncidentVisitorSplitLayout = $showPendingIncidentList && $canViewVisitorMonitoring;
                @endphp
                <div data-dashboard-tables class="[--dashboard-bottom-space:16px] min-[1600px]:[--dashboard-bottom-space:24px] mt-4 grid gap-3 {{ $usesIncidentVisitorSplitLayout ? 'lg:grid-cols-[3fr_2fr]' : '' }}">
                @if ($showPendingIncidentList)
                    <div data-dashboard-table x-data="dashboardTable({ endpoint: @js(route('dashboard.records', ['table' => 'incidents'])), cursor: @js($dashboardPendingIncidentList->nextCursor()?->encode()), cursorName: 'incident_cursor', searchKey: 'incident_q', query: @js((string) request('incident_q', '')), loaded: {{ $dashboardPendingIncidentList->count() }} })" class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-sm min-[1600px]:text-base font-semibold text-slate-900 sm:max-w-[45%]">Pending Incidents</h3>
                            <div class="w-full sm:min-w-0 sm:max-w-sm sm:flex-1">
                            <label class="sr-only" for="dashboard-incident-search">Search pending incidents</label>
                            <input id="dashboard-incident-search" type="search" x-model="query" @input="scheduleSearch()" @keydown.enter.prevent="reload()" placeholder="Search category, location, or description" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </div>

                        <div x-ref="viewport" tabindex="0" role="region" aria-label="Pending incidents" class="h-[min(45dvh,24rem)] overflow-auto overscroll-contain focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500"
                            @scroll.passive="onScroll()" @wheel.passive="if ($event.deltaY > 0) onScroll()"
                            @touchstart.passive="touchY = $event.touches[0].clientY"
                            @touchmove.passive="if ($event.touches[0].clientY < touchY) onScroll()"
                            @keydown="if (['ArrowDown', 'PageDown', 'End', ' '].includes($event.key) && $event.target === $el) onScroll()">
                            <table class="min-w-full text-sm min-[1600px]:text-base divide-y divide-slate-200">
                                <thead class="sticky top-0 z-10 h-10 bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-2 font-semibold text-left text-slate-600">Category</th>
                                        <th class="px-4 py-2 font-semibold text-left text-slate-600">Location</th>
                                        <th class="px-4 py-2 font-semibold text-left text-slate-600">Status</th>
                                        <th class="px-4 py-2 font-semibold text-left text-slate-600">Reported</th>
                                    </tr>
                                </thead>
                                <tbody x-ref="rows" class="bg-white divide-y divide-slate-100">
                                    @include('dashboard.partials.incidents-rows', ['records' => $dashboardPendingIncidentList])
                                </tbody>
                            </table>
                            <div x-show="cursor" class="h-px" aria-hidden="true"></div>
                        </div>

                        <div x-ref="footer" class="flex min-h-[32px] items-center justify-between border-t border-slate-200 px-4 py-1.5 text-[11px] min-[1600px]:text-xs text-slate-500" aria-live="polite">
                            <span x-text="message()"></span>
                            <button x-cloak x-show="error" type="button" @click="retry()" class="font-semibold text-brand-700 hover:underline">Retry</button>
                        </div>
                    </div>
                @endif

                @if ($canViewVisitorMonitoring)
                    <div data-dashboard-table x-data="dashboardTable({ endpoint: @js(route('dashboard.records', ['table' => 'visitors'])), cursor: @js($insideVisitors->nextCursor()?->encode()), cursorName: 'visitor_cursor', searchKey: 'visitor_q', query: @js((string) request('visitor_q', '')), loaded: {{ $insideVisitors->count() }} })" class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-sm min-[1600px]:text-base font-semibold text-slate-900 sm:max-w-[45%]">Visitors Currently Checked In</h3>
                            <div class="w-full sm:min-w-0 sm:max-w-sm sm:flex-1">
                            <label class="sr-only" for="dashboard-visitor-search">Search checked-in visitors</label>
                            <input id="dashboard-visitor-search" type="search" x-model="query" @input="scheduleSearch()" @keydown.enter.prevent="reload()" placeholder="Search visitor, resident, phone, or plate" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </div>

                        <div x-ref="viewport" tabindex="0" role="region" aria-label="Visitors currently checked in" class="h-[min(45dvh,24rem)] overflow-auto overscroll-contain focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500"
                            @scroll.passive="onScroll()" @wheel.passive="if ($event.deltaY > 0) onScroll()"
                            @touchstart.passive="touchY = $event.touches[0].clientY"
                            @touchmove.passive="if ($event.touches[0].clientY < touchY) onScroll()"
                            @keydown="if (['ArrowDown', 'PageDown', 'End', ' '].includes($event.key) && $event.target === $el) onScroll()">
                            <table class="min-w-full text-sm min-[1600px]:text-base divide-y divide-slate-200">
                                <thead class="sticky top-0 z-10 h-10 bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-2 font-semibold text-left text-slate-600">Visitor</th>
                                        <th class="px-4 py-2 font-semibold text-left text-slate-600">Plate Number</th>
                                        <th class="px-4 py-2 font-semibold text-right text-slate-600">Action</th>
                                    </tr>
                                </thead>
                                <tbody x-ref="rows" class="bg-white divide-y divide-slate-100">
                                    @include('dashboard.partials.visitors-rows', ['records' => $insideVisitors])
                                </tbody>
                            </table>
                            <div x-show="cursor" class="h-px" aria-hidden="true"></div>
                        </div>

                        <div x-ref="footer" class="flex min-h-[32px] items-center justify-between border-t border-slate-200 px-4 py-1.5 text-[11px] min-[1600px]:text-xs text-slate-500" aria-live="polite">
                            <span x-text="message()"></span>
                            <button x-cloak x-show="error" type="button" @click="retry()" class="font-semibold text-brand-700 hover:underline">Retry</button>
                        </div>
                    </div>
                @endif
                </div>
        </div>
    </div>
</x-app-layout>
