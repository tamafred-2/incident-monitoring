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
                                <dt class="text-slate-500">Resident Code</dt>
                                <dd class="font-medium text-right text-slate-900">{{ $resident->resident_code }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Phone</dt>
                                <dd class="font-medium text-right text-slate-900">{{ $resident->phone ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Email</dt>
                                <dd class="font-medium text-right text-slate-900">{{ $resident->email ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Created</dt>
                                <dd class="font-medium text-right text-slate-900">{{ $resident->created_at?->format('M j, Y h:i A') ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Housing and Access</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Assigned House</dt>
                                <dd class="font-medium text-right text-slate-900">{{ $resident->house?->display_address ?? 'Not assigned' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Street</dt>
                                <dd class="font-medium text-right text-slate-900">{{ $resident->house?->street ?: ($resident->address_or_unit ?: '-') }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Linked User Account</dt>
                                <dd class="font-medium text-right text-slate-900">{{ $resident->user?->email ?? 'Not linked' }}</dd>
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
                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Recent Verified Incidents</h4>
                            <p class="mt-1 text-sm text-slate-500">Complaint and verification history tied to this resident.</p>
                        </div>
                    </div>

                    @if ($resident->incidents->isNotEmpty())
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold text-left text-slate-600">Report ID</th>
                                        <th class="px-4 py-3 font-semibold text-left text-slate-600">Category</th>
                                        <th class="px-4 py-3 font-semibold text-left text-slate-600">Status</th>
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
                                            <td class="px-4 py-3 text-slate-600">{{ $incident->category ?: '-' }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $incident->status }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $incident->reporter?->full_name ?? 'System' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-500">No verified incidents are linked to this resident yet.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
