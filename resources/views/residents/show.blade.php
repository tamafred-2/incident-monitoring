<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Resident Details</h2>
                <p class="mt-1 text-sm text-slate-500">Review the resident profile, assigned house, and linked activity.</p>
            </div>
            <a
                href="{{ route('residents.index', $indexContext) }}"
                class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
            >
                Back to Residents
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="flex flex-col max-w-5xl gap-6 px-4 mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex flex-col gap-4 pb-5 border-b border-slate-200 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Resident Profile</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $resident->full_name }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $resident->subdivision?->subdivision_name ?? '-' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $resident->status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                        {{ $resident->status }}
                    </span>
                </div>

                <div class="grid gap-6 mt-6 lg:grid-cols-2">
                    <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Resident Info</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Phone</dt>
                                <dd class="font-medium text-right text-slate-900">{{ $resident->phone ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Email</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-all">{{ $resident->email ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Created</dt>
                                <dd class="font-medium text-right text-slate-900">
                                    @if ($resident->created_at)
                                        <span class="block whitespace-nowrap">{{ $resident->created_at->format('M j, Y') }}</span>
                                        <span class="mt-1 block whitespace-nowrap text-xs font-medium text-slate-500">{{ $resident->created_at->format('h:i A') }}</span>
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Housing and Access</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Assigned House</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-words">{{ $resident->house?->display_address ?? 'Not assigned' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Street</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-words">{{ $resident->house?->street ?: ($resident->address_or_unit ?: '-') }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Linked User Account</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-all">{{ $resident->user?->email ?? 'Not linked' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Verified Incidents</dt>
                                <dd class="font-medium text-right text-slate-900">{{ $resident->incidents->count() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="p-5 mt-6 bg-white border rounded-2xl border-slate-200">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Recent Incidents</h4>
                            <p class="mt-1 text-sm text-slate-500">Recent incident history tied to this resident record.</p>
                        </div>
                    </div>

                    @if ($resident->incidents->isNotEmpty())
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold text-left text-slate-600">Report ID</th>
                                        <th class="px-4 py-3 font-semibold text-left text-slate-600">Category</th>
                                        <th class="px-4 py-3 font-semibold text-left text-slate-600">Incident Status</th>
                                        <th class="px-4 py-3 font-semibold text-left text-slate-600">Reported By</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    @foreach ($resident->incidents->sortByDesc('created_at')->take(5) as $incident)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-slate-900">
                                                <a href="{{ route('incidents.show', $incident->incident_id) }}" class="font-mono hover:text-sky-700">
                                                    {{ $incident->report_id }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                <div class="max-w-[12rem] truncate" title="{{ $incident->category ?: '-' }}">{{ $incident->category ?: '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                @php
                                                    $isResolvedIncident = in_array($incident->status, ['Resolved', 'Closed'], true);
                                                    $incidentStatusLabel = $isResolvedIncident ? 'Resolved' : 'Pending';
                                                @endphp
                                                <span class="inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold {{ $isResolvedIncident ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                    {{ $incidentStatusLabel }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                <div class="max-w-[12rem] truncate" title="{{ $incident->reporter?->full_name ?? 'System' }}">{{ $incident->reporter?->full_name ?? 'System' }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-500">No incidents are linked to this resident yet.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
