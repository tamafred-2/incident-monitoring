<x-app-layout>
    <x-slot name="header">
        <x-page-header :compact="true" title="Dashboard" subtitle="Overview of visitor approvals, resident response tracking, and incident monitoring activity." />
    </x-slot>

    <div class="page-section">
        <div class="page-shell">
            @include('partials.alerts')

            @if ($isResidentDashboard)
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <x-stat-card :compact="true" label="Your Subdivision" :value="$totalSubdivisions" />
                    <x-stat-card :compact="true" label="Your Complaints" :value="$totalIncidents" />
                    <x-stat-card :compact="true" label="Pending Complaints" :value="$residentOpenIncidents" tone="amber" />
                    <x-stat-card :compact="true" label="Resolved Complaints" :value="$residentResolvedIncidents" tone="emerald" />
                </div>

                <div class="p-6 mt-8 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900">Resident Overview</h3>
                    <p class="mt-2 text-sm text-slate-500">Use the Incidents page to track pending or resolved reports tied to your account.</p>
                    <div class="grid gap-4 mt-6 md:grid-cols-2">
                        <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                            <p class="text-sm text-slate-500">Linked Resident Record</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ auth()->user()->resident?->full_name ?? 'Not linked' }}</p>
                        </div>
                        <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                            <p class="text-sm text-slate-500">Linked House</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ auth()->user()->resident?->house?->display_address ?? 'Not assigned' }}</p>
                        </div>
                    </div>
                </div>
            @else
                @php
                    $canViewVisitorMonitoring = auth()->user()->isAdmin() || auth()->user()->isSecurity();
                @endphp
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    <x-stat-card :compact="true" :href="route('incidents.index', ['view' => 'all'])" label="Total Incidents" tone="rose" :tinted="true" :value="$summary['total_incidents']" :hint="$summary['pending_incidents'] . ' pending'" />
                    <x-stat-card :compact="true" :href="route('visitors.index', ['tab' => 'history', 'chart_filter' => 1])" label="Total Visitors" tone="brand" :tinted="true" :value="$summary['total_visitors']" :hint="$summary['visitors_inside'] . ' currently inside'" />
                    <x-stat-card :compact="true" :href="route('residents.index')" label="Total Residents" tone="violet" :tinted="true" :value="$summary['total_residents']" />
                    <x-stat-card :compact="true" :href="auth()->user()->isAdmin() ? route('houses.index') : route('residents.index')" label="Total Houses" tone="teal" :tinted="true" :value="$summary['total_houses']" />
                </div>

                @php
                    $usesIncidentVisitorSplitLayout = $showPendingIncidentList && $canViewVisitorMonitoring;
                @endphp
                <div data-dashboard-tables class="[--dashboard-bottom-space:16px] min-[1600px]:[--dashboard-bottom-space:24px] mt-4 grid gap-3 {{ $usesIncidentVisitorSplitLayout ? 'lg:grid-cols-[3fr_2fr]' : '' }}">
                @if ($showPendingIncidentList)
                    <div data-dashboard-table x-data="dashboardTable({ endpoint: @js(route('dashboard.records', ['table' => 'incidents'])), cursor: @js($dashboardPendingIncidentList->nextCursor()?->encode()), cursorName: 'incident_cursor', loaded: {{ $dashboardPendingIncidentList->count() }} })" class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                        <div class="flex h-12 min-[1600px]:h-14 items-center px-4 border-b border-slate-200">
                            <div>
                                <h3 class="text-sm min-[1600px]:text-base font-semibold text-slate-900">Pending Incidents</h3>
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
                            <button x-cloak x-show="error" type="button" @click="loadMore()" class="font-semibold text-brand-700 hover:underline">Retry</button>
                        </div>
                    </div>
                @endif

                @if ($canViewVisitorMonitoring)
                    <div data-dashboard-table x-data="dashboardTable({ endpoint: @js(route('dashboard.records', ['table' => 'visitors'])), cursor: @js($insideVisitors->nextCursor()?->encode()), cursorName: 'visitor_cursor', loaded: {{ $insideVisitors->count() }} })" class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                        <div class="flex h-12 min-[1600px]:h-14 items-center px-4 border-b border-slate-200">
                            <div>
                                <h3 class="text-sm min-[1600px]:text-base font-semibold text-slate-900">Visitors Currently Checked In</h3>
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
                            <button x-cloak x-show="error" type="button" @click="loadMore()" class="font-semibold text-brand-700 hover:underline">Retry</button>
                        </div>
                    </div>
                @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
