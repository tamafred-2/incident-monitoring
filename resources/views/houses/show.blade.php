<x-app-layout>
    @php
        $isSecurityViewer = auth()->user()->role === 'security';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $isSecurityViewer ? 'House Contacts' : 'House Details' }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $isSecurityViewer ? 'Review residents and contact numbers in this house.' : 'Review the managed block and lot record.' }}</p>
            </div>
            <a
                href="{{ $backUrl }}"
                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                {{ $backLabel }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-4xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-200 pb-5">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">House Record</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $house->display_address }}</h3>
                    <p class="mt-2 text-sm text-slate-500">Subdivision: {{ $house->subdivision?->subdivision_name ?? '-' }}</p>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Record Summary</h4>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Block</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $house->block }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Lot</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $house->lot }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Address</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $house->display_address }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Street</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $house->street ?: '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Created</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $house->created_at?->format('M j, Y h:i A') ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Assigned Residents</h4>
                    @if ($house->residents->isNotEmpty())
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Resident</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Contact</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($house->residents as $resident)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-slate-900">{{ $resident->full_name }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $resident->phone ?: '-' }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $resident->status }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-500">No residents are linked to this house yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
