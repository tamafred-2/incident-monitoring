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

            @if ($isResidentDashboard)
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Your Subdivision</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalSubdivisions }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Your Complaints</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalIncidents }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Open Complaints</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $residentOpenIncidents }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Resolved Complaints</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $residentResolvedIncidents }}</p>
                    </div>
                </div>

                <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Resident Overview</h3>
                    <p class="mt-2 text-sm text-slate-500">Use the Incidents page to submit a complaint and track only the reports tied to your account.</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                            <p class="text-sm text-slate-500">Linked Resident Record</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ auth()->user()->resident?->full_name ?? 'Not linked' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                            <p class="text-sm text-slate-500">Linked House</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ auth()->user()->resident?->house?->display_address ?? 'Not assigned' }}</p>
                        </div>
                    </div>
                </div>
            @else
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

                <div class="mt-8">
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
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
