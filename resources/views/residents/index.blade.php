<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Residents</h2>
            <p class="mt-1 text-sm text-slate-500">Resident browsing and QR-card generation now live in the Laravel app.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('residents.index') }}" class="grid gap-4 md:grid-cols-[1fr_180px_220px_auto]">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Search</label>
                        <input type="search" name="q" value="{{ $filterQ }}" placeholder="Name, code, address"
                               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">All</option>
                            <option value="Active" @selected($filterStatus === 'Active')>Active</option>
                            <option value="Inactive" @selected($filterStatus === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                        <select name="subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="">All visible subdivisions</option>
                            @foreach ($subdivisions as $subdivision)
                                <option value="{{ $subdivision->subdivision_id }}" @selected($filterSubdivision === $subdivision->subdivision_id)>{{ $subdivision->subdivision_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Apply</button>
                        <a href="{{ route('residents.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Name</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Address / Unit</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Code</th>
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">Status</th>
                                @if ($subdivisions->isNotEmpty())
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Subdivision</th>
                                @endif
                                <th class="px-6 py-3 text-left font-semibold text-slate-600">QR Card</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($residents as $resident)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $resident->full_name }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $resident->address_or_unit ?: '-' }}</td>
                                    <td class="px-6 py-4 text-slate-600"><code>{{ $resident->resident_code }}</code></td>
                                    <td class="px-6 py-4 text-slate-600">{{ $resident->status }}</td>
                                    @if ($subdivisions->isNotEmpty())
                                        <td class="px-6 py-4 text-slate-600">{{ $resident->subdivision->subdivision_name ?? '-' }}</td>
                                    @endif
                                    <td class="px-6 py-4">
                                        <a href="{{ route('residents.qr-card', $resident) }}" target="_blank" class="inline-flex rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Open QR Card
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $subdivisions->isNotEmpty() ? 6 : 5 }}" class="px-6 py-10 text-center text-slate-500">No residents found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
