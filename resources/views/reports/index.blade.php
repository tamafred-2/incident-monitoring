<x-app-layout>
    <x-slot name="header"><x-page-header title="Reports" subtitle="Choose records, apply filters, and download a report." /></x-slot>
    <div class="page-section"><div class="page-shell space-y-4">
        @include('partials.alerts')
        <nav class="flex gap-2" aria-label="Report types">
            <a href="{{ route('reports.index', ['type' => 'incidents']) }}" class="rounded-xl border px-4 py-2 text-sm font-semibold {{ $type === 'incidents' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700' }}">Incident Reports</a>
            @if ($canReportVisitors)<a href="{{ route('reports.index', ['type' => 'visitors']) }}" class="rounded-xl border px-4 py-2 text-sm font-semibold {{ $type === 'visitors' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700' }}">Visitor Reports</a>@endif
        </nav>
        <form method="GET" action="{{ route('reports.index') }}" x-data="{ allDates: @js((bool) ($filters['all_dates'] ?? false)) }" class="max-w-5xl rounded-2xl border border-slate-200 bg-white p-5">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div><label for="report-search" class="block text-sm font-medium text-slate-700">Search</label><input id="report-search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $type === 'visitors' ? 'Visitor, phone, host, house' : 'Category, location, reporter' }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></div>
                <div><label for="report-start" class="block text-sm font-medium text-slate-700">Start</label><input id="report-start" type="datetime-local" step="1" name="date_from" :disabled="allDates" value="{{ isset($filters['date_from']) ? \Illuminate\Support\Carbon::parse($filters['date_from'])->format('Y-m-d\TH:i:s') : '' }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><x-input-error :messages="$errors->get('date_from')" /></div>
                <div><label for="report-end" class="block text-sm font-medium text-slate-700">End</label><input id="report-end" type="datetime-local" step="1" name="date_to" :disabled="allDates" value="{{ isset($filters['date_to']) ? \Illuminate\Support\Carbon::parse($filters['date_to'])->format('Y-m-d\TH:i:s') : '' }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><x-input-error :messages="$errors->get('date_to')" /></div>
                <div><label for="report-status" class="block text-sm font-medium text-slate-700">Records</label>
                    @if ($type === 'incidents')<select id="report-status" name="view" class="mt-1 w-full rounded-xl border-slate-300 text-sm">@foreach (['all' => 'All incidents', 'active' => 'Active incidents', 'history' => 'Resolved / Closed'] as $value => $label)<option value="{{ $value }}" @selected(($filters['view'] ?? 'all') === $value)>{{ $label }}</option>@endforeach</select>
                    @else<select id="report-status" name="report_status" class="mt-1 w-full rounded-xl border-slate-300 text-sm">@foreach (['' => 'All visits', 'Inside' => 'Currently inside', 'Checked Out' => 'Checked out'] as $value => $label)<option value="{{ $value }}" @selected(($filters['report_status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>@endif
                </div>
                @if (auth()->user()->isAdmin())<div><label for="report-subdivision" class="block text-sm font-medium text-slate-700">Subdivision</label><select id="report-subdivision" name="subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">All subdivisions</option>@foreach ($subdivisions as $subdivision)<option value="{{ $subdivision->getKey() }}" @selected(($filters['subdivision_id'] ?? '') == $subdivision->getKey())>{{ $subdivision->subdivision_name }}</option>@endforeach</select></div>@endif
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="all_dates" value="1" x-model="allDates">All dates (download in parts of up to 100 records)</label>
            <div class="mt-4 flex flex-wrap items-center gap-3"><button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Generate Report</button><a href="{{ route('reports.index', ['type' => $type]) }}" class="text-sm font-semibold text-slate-600">Reset Filters</a></div>
            <p class="mt-3 text-xs text-slate-500">Dates filter {{ $type === 'visitors' ? 'check-in time' : 'date reported' }}. Each file contains at most 100 records. For larger reports, download each numbered part separately.</p>
        </form>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5"><div><h3 class="font-semibold text-slate-900">Report Preview - Part {{ $part }} of {{ $totalParts }}</h3><p class="mt-1 text-sm text-slate-500">{{ number_format($totalRecords) }} matching records</p></div><div class="flex flex-wrap gap-2"><a href="{{ $pdfUrl }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Download PDF - Part {{ $part }}</a><a href="{{ $excelUrl }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Download Excel (CSV) - Part {{ $part }}</a></div></div>
            @if ($totalParts > 1)
                <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-center gap-3 border-b border-slate-200 px-5 py-3">
                    <input type="hidden" name="type" value="{{ $type }}">
                    @foreach ($filters as $key => $value)
                        @if ($key !== 'report_part')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label for="report-part" class="text-sm font-medium text-slate-700">Download part</label>
                    <select id="report-part" name="report_part" class="rounded-xl border-slate-300 text-sm">
                        @for ($number = 1; $number <= $totalParts; $number++)<option value="{{ $number }}" @selected($part === $number)>Part {{ $number }} (records {{ ($number - 1) * 100 + 1 }}-{{ min($number * 100, $totalRecords) }})</option>@endfor
                    </select>
                    <button class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold">Show Part</button>
                    @if ($part < $totalParts)<a href="{{ route('reports.index', array_merge($filters, ['type' => $type, 'report_part' => $part + 1])) }}" class="text-sm font-semibold text-brand-700">Next Part</a>@endif
                    <p class="text-xs text-slate-500">Download the PDF or Excel file for each part to get all matching records.</p>
                </form>
            @endif
            <iframe src="{{ $previewUrl }}" title="{{ $type === 'visitors' ? 'Visitor' : 'Incident' }} report preview" class="h-[70vh] w-full border-0"></iframe>
        </section>
    </div></div>
</x-app-layout>
