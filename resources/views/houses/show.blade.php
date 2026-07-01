<x-app-layout>
    @php $isSecurityViewer = auth()->user()->isSecurity(); @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $house->display_address }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $house->subdivision?->subdivision_name ?? '-' }}</p>
            </div>
            @if ($house->subdivision)
                <div class="flex items-center gap-2">
                    <a href="{{ route('subdivisions.show', $house->subdivision) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        View Subdivision Contact Info
                    </a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('subdivisions.edit', $house->subdivision) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                            Edit Subdivision Profile
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-3xl flex-col gap-5 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            {{-- Details --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Record Details</p>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-xs text-slate-400">Block</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $house->block }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400">Lot</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $house->lot }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400">Street</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $house->street ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400">Added</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $house->created_at?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Residents --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <p class="text-sm font-semibold text-slate-900">Residents <span class="ml-1.5 text-xs font-normal text-slate-400">{{ $house->residents->count() }}</span></p>
                    @php $ownerResident = $house->residents->first(fn ($r) => strcasecmp((string) $r->relation_to_owner, 'Owner') === 0); @endphp
                    @if ($ownerResident)
                        <span class="text-xs text-slate-500">Owner: <span class="font-medium text-slate-700">{{ $ownerResident->full_name }}</span></span>
                    @endif
                </div>
                @if ($house->residents->isNotEmpty())
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                <th class="px-6 py-3 text-left">Name</th>
                                <th class="px-6 py-3 text-left">Relation</th>
                                <th class="px-6 py-3 text-left">Contact</th>
                                <th class="px-6 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($house->residents as $resident)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-3 font-medium text-slate-900">{{ $resident->full_name }}</td>
                                    <td class="px-6 py-3 text-slate-500">{{ $resident->relation_to_owner ?: '—' }}</td>
                                    <td class="px-6 py-3 text-slate-500">{{ $resident->phone ?: '—' }}</td>
                                    <td class="px-6 py-3 text-slate-500">{{ $resident->status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="px-6 py-8 text-center text-sm text-slate-400">No residents linked to this house yet.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
