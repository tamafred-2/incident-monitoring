<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Incidents</h2>
                <p class="mt-1 text-sm text-slate-500">Incident reporting, resident verification, and proof-photo uploads now live in Laravel.</p>
            </div>
            @if (auth()->user()->hasRole(['security', 'staff', 'investigator']))
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'report-incident')"
                    class="inline-flex items-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700"
                >
                    Report Incident
                </button>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            @if ($subdivisions->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <form method="GET" action="{{ route('incidents.index') }}" class="flex flex-wrap items-end gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                            <select name="subdivision_id" class="mt-1 rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                <option value="">All subdivisions</option>
                                @foreach ($subdivisions as $subdivision)
                                    <option value="{{ $subdivision->subdivision_id }}" @selected($filterSubdivision === $subdivision->subdivision_id)>{{ $subdivision->subdivision_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Apply</button>
                    </form>
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Title</th>
                                @if ($subdivisions->isNotEmpty())
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Subdivision</th>
                                @endif
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Category</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Date</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Verified Reporter</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Proof</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($incidents as $incident)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $incident->title }}</td>
                                    @if ($subdivisions->isNotEmpty())
                                        <td class="px-6 py-4 text-slate-600">{{ $incident->subdivision->subdivision_name ?? '-' }}</td>
                                    @endif
                                    <td class="px-6 py-4 text-slate-600">{{ $incident->category ?: '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ optional($incident->incident_date)->format('M j, Y H:i') }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $incident->status }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $incident->verifiedResident?->full_name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600">
                                        @if ($incident->proofPhotos->isNotEmpty())
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($incident->proofPhotos as $photo)
                                                    <a href="{{ asset($photo->photo_path) }}" target="_blank" class="font-medium text-sky-700 hover:text-sky-900">
                                                        View {{ $loop->iteration }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @elseif ($incident->proof_photo_path)
                                            <a href="{{ asset($incident->proof_photo_path) }}" target="_blank" class="font-medium text-sky-700 hover:text-sky-900">View 1</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $subdivisions->isNotEmpty() ? 7 : 6 }}" class="px-6 py-10 text-center text-slate-500">No incidents found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (auth()->user()->hasRole(['security', 'staff', 'investigator']))
                @include('incidents.partials.report-modal')
            @endif
        </div>
    </div>
</x-app-layout>
