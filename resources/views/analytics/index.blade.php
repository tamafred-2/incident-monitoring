<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Analytics" :subtitle="'Incident, visitor, and community trends — ' . $scopeLabel . '.'" />
    </x-slot>

    <div class="py-5">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            {{-- Incident analytics --}}
            <section class="space-y-2">
                <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Incident Trends</h3>
                <div class="grid gap-3 xl:grid-cols-[3fr_2fr]">
                    <x-analytics-card title="Incidents per Month" subtitle="Last 12 months by reported date">
                        <canvas data-chart="incidentsMonthly" height="88"></canvas>
                    </x-analytics-card>
                    <x-analytics-card title="By Status">
                        <canvas data-chart="incidentsStatus" height="88"></canvas>
                    </x-analytics-card>
                    <x-analytics-card title="By Category" class="xl:col-span-2">
                        <canvas data-chart="incidentsCategory" height="72"></canvas>
                    </x-analytics-card>
                </div>
            </section>

            {{-- Visitor analytics --}}
            <section class="space-y-2">
                <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Visitor Trends</h3>
                <div class="grid gap-3 xl:grid-cols-2">
                    <x-analytics-card title="Check-ins per Month" subtitle="Last 12 months">
                        <canvas data-chart="visitorsMonthly" height="88"></canvas>
                    </x-analytics-card>
                    <x-analytics-card title="Check-ins by Day of Week">
                        <canvas data-chart="visitorsWeekday" height="88"></canvas>
                    </x-analytics-card>
                </div>
            </section>

            {{-- Community analytics --}}
            <section class="space-y-2">
                <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Residents &amp; Houses</h3>
                <div class="grid gap-3 xl:grid-cols-2">
                    <x-analytics-card title="Residents by Relation to Owner">
                        @if (count($community['relation_labels']) > 0)
                            <canvas data-chart="residentsRelation" height="88"></canvas>
                        @else
                            <p class="py-8 text-center text-xs text-slate-500">No resident relation data available.</p>
                        @endif
                    </x-analytics-card>
                    <x-analytics-card title="Top Houses by Incident Count">
                        @if (count($community['top_house_labels']) > 0)
                            <canvas data-chart="topHouses" height="88"></canvas>
                        @else
                            <p class="py-8 text-center text-xs text-slate-500">No incidents linked to houses yet.</p>
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
            const gridColor = 'rgba(148, 163, 184, 0.12)';

            Chart.defaults.font.family = "'Figtree', system-ui, sans-serif";
            Chart.defaults.font.size = 11;
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
                    y: { beginAtZero: true, border: { display: false }, ticks: { precision: 0, font: { size: 10 } }, grid: { color: gridColor } },
                    x: { border: { display: false }, ticks: { font: { size: 10 } }, grid: { display: false } },
                },
            };

            const verticalBarOptions = {
                ...axisOptions,
                scales: {
                    ...axisOptions.scales,
                    x: { ...axisOptions.scales.x, offset: true },
                },
            };

            const doughnutOptions = {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxHeight: 8, boxWidth: 8, padding: 10, font: { size: 10 } },
                    },
                },
            };

            render('incidentsMonthly', (set) => ({
                type: 'line',
                data: {
                    labels: set.labels,
                    datasets: [{
                        data: set.values,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.12)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
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
                    datasets: [{ data: set.values, backgroundColor: '#6366f1', borderRadius: 4, barThickness: 30 }],
                },
                options: verticalBarOptions,
            }));

            render('visitorsMonthly', (set) => ({
                type: 'line',
                data: {
                    labels: set.labels,
                    datasets: [{
                        data: set.values,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                    }],
                },
                options: verticalBarOptions,
            }));

            render('visitorsWeekday', (set) => ({
                type: 'bar',
                data: {
                    labels: set.labels,
                    datasets: [{ data: set.values, backgroundColor: '#0ea5e9', borderRadius: 4, barThickness: 30 }],
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
                    datasets: [{ data: set.values, backgroundColor: '#f43f5e', borderRadius: 4, barThickness: 22 }],
                },
                options: {
                    ...axisOptions,
                    indexAxis: 'y',
                    scales: {
                        x: { beginAtZero: true, border: { display: false }, ticks: { precision: 0, font: { size: 10 } }, grid: { color: gridColor } },
                        y: { border: { display: false }, ticks: { font: { size: 10 } }, grid: { display: false } },
                    },
                },
            }));
        })();
    </script>
</x-app-layout>
