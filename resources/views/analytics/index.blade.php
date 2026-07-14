<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Analytics" :subtitle="'Incident, visitor, and community trends — ' . $scopeLabel . '.'" />
    </x-slot>

    <div class="py-5">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            {{-- Granularity + date range. Scopes the trend, By Status, and By Category. --}}
            <form method="GET" action="{{ route('analytics.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div>
                    <label class="block text-xs font-medium text-slate-500">View by</label>
                    <select name="granularity" onchange="this.form.requestSubmit()" class="mt-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @foreach ($granularities as $g)
                            <option value="{{ $g }}" @selected($granularity === $g)>{{ ucfirst($g) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">From</label>
                    <input type="date" name="from" value="{{ $filterFrom }}" max="{{ $filterTo }}" onchange="this.form.requestSubmit()"
                           class="mt-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">To</label>
                    <input type="date" name="to" value="{{ $filterTo }}" min="{{ $filterFrom }}" onchange="this.form.requestSubmit()"
                           class="mt-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                @if ($hasRange)
                    <a href="{{ route('analytics.index', ['granularity' => $granularity]) }}"
                       class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">Reset to this month</a>
                @endif
                <p class="ml-auto self-center text-xs text-slate-500">Showing: <span class="font-semibold text-slate-700">{{ $rangeLabel }}</span></p>
            </form>

            {{-- Incident analytics --}}
            <section class="space-y-2">
                <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Incident Trends</h3>
                <div class="grid gap-3 xl:grid-cols-[3fr_2fr]">
                    <x-analytics-card title="Incident Trend" subtitle="{{ ucfirst($granularity) }} totals · {{ $rangeLabel }}">
                        <canvas data-chart="incidentsTrend" height="88"></canvas>
                    </x-analytics-card>
                    <x-analytics-card title="By Status" subtitle="{{ $rangeLabel }}">
                        <canvas data-chart="incidentsStatus" height="88"></canvas>
                    </x-analytics-card>
                    <x-analytics-card title="By Category" subtitle="{{ $rangeLabel }}" class="xl:col-span-2">
                        <canvas data-chart="incidentsCategory" height="72"></canvas>
                    </x-analytics-card>
                </div>
            </section>

            {{-- Visitor analytics --}}
            <section class="space-y-2">
                <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Visitor Trends</h3>
                <div class="grid gap-3 xl:grid-cols-2">
                    <x-analytics-card title="Visitor Check-in Trend" subtitle="{{ ucfirst($granularity) }} totals · {{ $rangeLabel }}">
                        <canvas data-chart="visitorsTrend" height="88"></canvas>
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
            'incidentsTrend' => $incidents['trend'],
            'incidentsStatus' => ['labels' => $incidents['status_labels'], 'values' => $incidents['status_values']],
            'incidentsCategory' => ['labels' => $incidents['category_labels'], 'values' => $incidents['category_values']],
            'visitorsTrend' => $visitors['trend'],
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
            // CVD-validated categorical palette — fixed slot order, never cycled.
            const palette = ['#2a78d6', '#1baf7a', '#eda100', '#008300', '#4a3aa7', '#e34948', '#e87ba4', '#eb6834'];
            const brand = '#2a78d6';
            const brandFill = 'rgba(42, 120, 214, 0.10)';
            const aqua = '#1baf7a';
            const aquaFill = 'rgba(27, 175, 122, 0.10)';
            const gridColor = 'rgba(148, 163, 184, 0.16)';

            // Status slices reuse the same hues as the status badges so meaning stays consistent.
            const statusColors = (labels) => labels.map((label) => {
                const key = String(label).toLowerCase();
                if (/resolve|close|done|complete|approve|active/.test(key)) return '#10b981';
                if (/investigat|inside/.test(key)) return brand;
                if (/declin|reject/.test(key)) return '#e34948';
                if (/pend|open|report/.test(key)) return '#f59e0b';
                return '#94a3b8';
            });

            Chart.defaults.font.family = "'Figtree', system-ui, sans-serif";
            Chart.defaults.font.size = 11;
            Chart.defaults.color = '#64748b';
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.92)';
            Chart.defaults.plugins.tooltip.padding = 10;
            Chart.defaults.plugins.tooltip.cornerRadius = 8;
            Chart.defaults.plugins.tooltip.titleFont = { weight: '600' };
            Chart.defaults.plugins.tooltip.displayColors = false;

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
                        labels: { boxHeight: 8, boxWidth: 8, padding: 10, font: { size: 10 }, usePointStyle: true, pointStyle: 'circle' },
                    },
                },
            };

            // 2px white spacer between doughnut segments so adjacent fills never touch.
            const doughnutDataset = (values, colors) => ({
                data: values,
                backgroundColor: colors,
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 4,
            });

            // Time-series trend charts. Each point is a visible dot; hovering a dot
            // shows the total for that period (the granularity/range come from the filter).
            const lineTrend = (set, color, fillColor) => ({
                type: 'line',
                data: {
                    labels: set.labels,
                    datasets: [{
                        data: set.values,
                        borderColor: color,
                        backgroundColor: fillColor,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: color,
                        pointBorderWidth: 2,
                    }],
                },
                options: {
                    ...axisOptions,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (item) => ' Total: ' + item.parsed.y } },
                    },
                    scales: {
                        ...axisOptions.scales,
                        x: { ...axisOptions.scales.x, ticks: { ...axisOptions.scales.x.ticks, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } },
                    },
                },
            });

            render('incidentsTrend', (set) => lineTrend(set, brand, brandFill));

            render('incidentsStatus', (set) => ({
                type: 'doughnut',
                data: {
                    labels: set.labels,
                    datasets: [doughnutDataset(set.values, statusColors(set.labels))],
                },
                options: doughnutOptions,
            }));

            render('incidentsCategory', (set) => ({
                type: 'bar',
                data: {
                    labels: set.labels,
                    datasets: [{ data: set.values, backgroundColor: brand, borderRadius: { topLeft: 4, topRight: 4 }, maxBarThickness: 40 }],
                },
                options: verticalBarOptions,
            }));

            render('visitorsTrend', (set) => lineTrend(set, aqua, aquaFill));

            render('visitorsWeekday', (set) => ({
                type: 'bar',
                data: {
                    labels: set.labels,
                    datasets: [{ data: set.values, backgroundColor: aqua, borderRadius: { topLeft: 4, topRight: 4 }, maxBarThickness: 40 }],
                },
                options: verticalBarOptions,
            }));

            render('residentsRelation', (set) => ({
                type: 'doughnut',
                data: {
                    labels: set.labels,
                    datasets: [doughnutDataset(set.values, palette)],
                },
                options: doughnutOptions,
            }));

            render('topHouses', (set) => ({
                type: 'bar',
                data: {
                    labels: set.labels,
                    datasets: [{ data: set.values, backgroundColor: brand, borderRadius: { topRight: 4, bottomRight: 4 }, barThickness: 18 }],
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
