<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Analytics" :subtitle="'Incident, visitor, and community trends — ' . $scopeLabel . '.'" />
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            @include('partials.alerts')

            {{-- Summary stat cards --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Total Incidents" :value="$summary['total_incidents']" :hint="$summary['pending_incidents'] . ' pending'" />
                <x-stat-card label="Resolution Rate" :value="$summary['resolution_rate'] . '%'" :hint="$summary['resolved_incidents'] . ' resolved'" tone="emerald" />
                <x-stat-card label="Avg. Resolution Time" :value="$summary['avg_resolution_label']" hint="reported → resolved" />
                <x-stat-card label="Total Visitors" :value="$summary['total_visitors']" :hint="$summary['visitors_inside'] . ' currently inside'" />
                <x-stat-card label="Total Residents" :value="$summary['total_residents']" />
                <x-stat-card label="Total Houses" :value="$summary['total_houses']" />
                <x-stat-card label="Avg. Residents / House" :value="$community['avg_residents_per_house']" />
                <x-stat-card label="Pending Incidents" :value="$summary['pending_incidents']" tone="amber" />
            </div>

            {{-- Incident analytics --}}
            <section class="space-y-4">
                <h3 class="text-lg font-semibold text-slate-900">Incident Trends</h3>
                <div class="grid gap-6 xl:grid-cols-[3fr_2fr]">
                    <x-analytics-card title="Incidents per Month" subtitle="Last 12 months by reported date">
                        <canvas data-chart="incidentsMonthly" height="120"></canvas>
                    </x-analytics-card>
                    <x-analytics-card title="By Status">
                        <canvas data-chart="incidentsStatus" height="120"></canvas>
                    </x-analytics-card>
                    <x-analytics-card title="By Category" class="xl:col-span-2">
                        <canvas data-chart="incidentsCategory" height="80"></canvas>
                    </x-analytics-card>
                </div>
            </section>

            {{-- Visitor analytics --}}
            <section class="space-y-4">
                <h3 class="text-lg font-semibold text-slate-900">Visitor Trends</h3>
                <div class="grid gap-6 xl:grid-cols-2">
                    <x-analytics-card title="Check-ins per Month" subtitle="Last 12 months">
                        <canvas data-chart="visitorsMonthly" height="140"></canvas>
                    </x-analytics-card>
                    <x-analytics-card title="Check-ins by Day of Week">
                        <canvas data-chart="visitorsWeekday" height="140"></canvas>
                    </x-analytics-card>
                </div>
            </section>

            {{-- Community analytics --}}
            <section class="space-y-4">
                <h3 class="text-lg font-semibold text-slate-900">Residents &amp; Houses</h3>
                <div class="grid gap-6 xl:grid-cols-2">
                    <x-analytics-card title="Residents by Relation to Owner">
                        @if (count($community['relation_labels']) > 0)
                            <canvas data-chart="residentsRelation" height="140"></canvas>
                        @else
                            <p class="py-12 text-center text-sm text-slate-500">No resident relation data available.</p>
                        @endif
                    </x-analytics-card>
                    <x-analytics-card title="Top Houses by Incident Count">
                        @if (count($community['top_house_labels']) > 0)
                            <canvas data-chart="topHouses" height="140"></canvas>
                        @else
                            <p class="py-12 text-center text-sm text-slate-500">No incidents linked to houses yet.</p>
                        @endif
                    </x-analytics-card>
                </div>
            </section>
        </div>
    </div>

    @php
        $chartData = [
            'incidentsMonthly' => ['labels' => $incidents['monthly_labels'], 'values' => $incidents['monthly_values']],
            'incidentsStatus' => ['labels' => $incidents['status_labels'], 'values' => $incidents['status_values']],
            'incidentsCategory' => ['labels' => $incidents['category_labels'], 'values' => $incidents['category_values']],
            'visitorsMonthly' => ['labels' => $visitors['monthly_labels'], 'values' => $visitors['monthly_values']],
            'visitorsWeekday' => ['labels' => $visitors['weekday_labels'], 'values' => $visitors['weekday_values']],
            'residentsRelation' => ['labels' => $community['relation_labels'], 'values' => $community['relation_values']],
            'topHouses' => ['labels' => $community['top_house_labels'], 'values' => $community['top_house_values']],
        ];
    @endphp

    <script>
        window.__analyticsData = @json($chartData);

        (function initAnalyticsCharts() {
            if (typeof window.Chart === 'undefined') {
                window.requestAnimationFrame(initAnalyticsCharts);
                return;
            }

            const data = window.__analyticsData || {};
            const palette = ['#0ea5e9', '#6366f1', '#f43f5e', '#f59e0b', '#10b981', '#8b5cf6', '#ec4899', '#14b8a6', '#eab308', '#64748b'];
            const gridColor = 'rgba(148, 163, 184, 0.15)';

            Chart.defaults.font.family = "'Figtree', system-ui, sans-serif";
            Chart.defaults.color = '#64748b';

            const render = (key, builder) => {
                const canvas = document.querySelector(`canvas[data-chart="${key}"]`);
                const set = data[key];
                if (!canvas || !set) {
                    return;
                }
                new Chart(canvas.getContext('2d'), builder(set));
            };

            const axisOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } },
                    x: { grid: { display: false } },
                },
            };

            const doughnutOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } },
            };

            render('incidentsMonthly', (set) => ({
                type: 'line',
                data: {
                    labels: set.labels,
                    datasets: [{
                        data: set.values,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                    }],
                },
                options: axisOptions,
            }));

            render('incidentsStatus', (set) => ({
                type: 'doughnut',
                data: {
                    labels: set.labels,
                    datasets: [{ data: set.values, backgroundColor: palette }],
                },
                options: doughnutOptions,
            }));

            render('incidentsCategory', (set) => ({
                type: 'bar',
                data: {
                    labels: set.labels,
                    datasets: [{ data: set.values, backgroundColor: '#6366f1', borderRadius: 6 }],
                },
                options: axisOptions,
            }));

            render('visitorsMonthly', (set) => ({
                type: 'line',
                data: {
                    labels: set.labels,
                    datasets: [{
                        data: set.values,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                    }],
                },
                options: axisOptions,
            }));

            render('visitorsWeekday', (set) => ({
                type: 'bar',
                data: {
                    labels: set.labels,
                    datasets: [{ data: set.values, backgroundColor: '#0ea5e9', borderRadius: 6 }],
                },
                options: axisOptions,
            }));

            render('residentsRelation', (set) => ({
                type: 'doughnut',
                data: {
                    labels: set.labels,
                    datasets: [{ data: set.values, backgroundColor: palette }],
                },
                options: doughnutOptions,
            }));

            render('topHouses', (set) => ({
                type: 'bar',
                data: {
                    labels: set.labels,
                    datasets: [{ data: set.values, backgroundColor: '#f43f5e', borderRadius: 6 }],
                },
                options: {
                    ...axisOptions,
                    indexAxis: 'y',
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } },
                        y: { grid: { display: false } },
                    },
                },
            }));
        })();
    </script>
</x-app-layout>