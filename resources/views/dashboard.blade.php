<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
            <p class="mt-1 text-sm text-slate-500">Overview of all subdivisions and current monitoring activity.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Subdivisions</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalSubdivisions }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Incidents</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalIncidents }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Residents</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalResidents }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Managed Houses</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalHouses }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Visitors Today</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $visitorsToday }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Visitors Inside</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $visitorsInside }}</p>
                </div>
            </div>

            <div class="mt-8 grid gap-8 xl:grid-cols-2">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Visitors Currently Checked In</h3>
                            <p class="mt-1 text-sm text-slate-500">Compact live list. Click a visitor name to open the full details.</p>
                        </div>

                        <form method="GET" action="{{ route('dashboard') }}" class="flex items-end gap-3">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Per Page</label>
                                <select
                                    name="inside_per_page"
                                    onchange="this.form.submit()"
                                    class="mt-1 rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                >
                                    <option value="5" @selected($insidePerPage === 5)>5</option>
                                    <option value="10" @selected($insidePerPage === 10)>10</option>
                                </select>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Subdivision</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Visitor</th>
                                </tr>
                            </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($insideVisitors as $visitor)
                                <tr
                                    class="cursor-pointer transition hover:bg-slate-50 focus-within:bg-slate-50"
                                    role="link"
                                    tabindex="0"
                                    onclick="window.location='{{ route('visitors.show', array_merge(['visitor' => $visitor], request()->only(['inside_per_page', 'page']))) }}'"
                                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location='{{ route('visitors.show', array_merge(['visitor' => $visitor], request()->only(['inside_per_page', 'page']))) }}'; }"
                                >
                                    <td class="px-6 py-4 text-slate-600">{{ $visitor->subdivision->subdivision_name ?? '-' }}</td>
                                    <td class="px-6 py-4 font-medium text-sky-700">{{ $visitor->full_name }}</td>
                                </tr>
                            @empty
                                    <tr>
                                        <td colspan="2" class="px-6 py-10 text-center text-slate-500">
                                            No visitors are currently checked in.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($insideVisitors->total() >= $insidePerPage)
                        <div class="border-t border-slate-200 px-6 py-4">
                            {{ $insideVisitors->links() }}
                        </div>
                    @endif
                </div>

                @if ($breakdown->isNotEmpty())
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-900">Subdivision Breakdown</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Subdivision</th>
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Residents</th>
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Houses</th>
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Occupied Houses</th>
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Incidents</th>
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Visitors Inside</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($breakdown as $subdivision)
                                        <tr>
                                            <td class="px-6 py-4 font-medium text-slate-900">{{ $subdivision->subdivision_name }}</td>
                                            <td class="px-6 py-4 text-slate-700">{{ $subdivision->residents_count }}</td>
                                            <td class="px-6 py-4 text-slate-700">{{ $subdivision->houses_count }}</td>
                                            <td class="px-6 py-4 text-slate-700">{{ $subdivision->occupied_houses_count }}</td>
                                            <td class="px-6 py-4 text-slate-700">{{ $subdivision->incidents_count }}</td>
                                            <td class="px-6 py-4 text-slate-700">{{ $subdivision->visitors_inside_count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
