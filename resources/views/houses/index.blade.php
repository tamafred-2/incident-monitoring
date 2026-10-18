<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="page-title">House Registry</h2>
            <p class="page-subtitle">Block and lot records for each subdivision.</p>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="page-shell flex flex-col gap-4 min-[1600px]:gap-6">
            @include('partials.alerts')

            {{-- Summary banner --}}
            @php
                $summarySubdivision = $filterSubdivision
                    ? $subdivisions->firstWhere('subdivision_id', (int) $filterSubdivision)
                    : $subdivisions->first();
                $totalHouses = $houses->count();
                $totalResidents = $houses->sum(fn ($h) => $h->residents->count());
            @endphp
            @if ($summarySubdivision)
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">House Registry Overview</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Manage and maintain block and lot records for <span class="font-medium text-slate-700">{{ $summarySubdivision->subdivision_name }}</span>.
                    </p>
                    <div class="mt-3 flex items-center gap-5 text-sm text-slate-600">
                        <span><span class="font-semibold text-slate-900">{{ $totalHouses }}</span> {{ Str::plural('house', $totalHouses) }}</span>
                        <span class="text-slate-300">|</span>
                        <span><span class="font-semibold text-slate-900">{{ $totalResidents }}</span> {{ Str::plural('resident', $totalResidents) }}</span>
                    </div>
                </div>
            @endif
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('houses.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[200px] flex-1">
                        <label class="block text-xs font-medium text-slate-500">Search</label>
                        <input type="search" name="q" value="{{ $filterQ }}" placeholder="Street, block, or lot"
                               data-live-search="#houses-results,#houses-scroll-modals" autocomplete="off"
                               class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    @if ($subdivisions->count() > 1)
                        <div class="min-w-[180px]">
                            <label class="block text-xs font-medium text-slate-500">Subdivision</label>
                            <select name="subdivision_id" onchange="this.form.requestSubmit()" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">All</option>
                                @foreach ($subdivisions as $subdivision)
                                    <option value="{{ $subdivision->subdivision_id }}" @selected((string) $filterSubdivision === (string) $subdivision->subdivision_id)>
                                        {{ $subdivision->subdivision_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="flex items-end gap-2">
                        <button
                            type="button"
                            x-data
                            x-on:click="$dispatch('open-modal', 'create-house')"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            + Add House
                        </button>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div id="houses-results"><x-scroll-list name="houses" :records="$houses" modal-target="#houses-scroll-modals">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                <th class="px-6 py-3 text-left">Address</th>
                                <th class="px-6 py-3 text-left">Street</th>
                                <th class="px-6 py-3 text-left">Owner</th>
                                <th class="px-6 py-3 text-left">Residents</th>
                                <th class="px-6 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody data-scroll-rows id="houses-rows" class="divide-y divide-slate-100 bg-white">
@include('scroll-lists.houses')
</tbody>
                    </table>
                </x-scroll-list></div>
            </div>

            {{-- Create modal --}}
            <x-modal name="create-house" :show="$errors->houseCreate->any()" maxWidth="2xl" focusable>
                <div class="bg-white p-6 sm:p-8" x-data x-on:open-modal.window="if ($event.detail === 'create-house') { $nextTick(() => { $el.querySelectorAll('input:not([type=hidden])').forEach(i => i.value = ''); $el.querySelectorAll('select').forEach(s => s.selectedIndex = 0); }); }">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Add House</h3>
                            <p class="mt-1 text-sm text-slate-500">Create a house and assign its owner.</p>
                        </div>
                        <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-600" aria-label="Close">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 01-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('houses.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="house_form_mode" value="create">
                        @include('houses.partials.form-fields', ['errorBag' => 'houseCreate'])
                        @include('houses.partials.owner-fields')
                        <div class="flex gap-2 pt-1">
                            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Save</button>
                            <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                        </div>
                    </form>
                </div>
            </x-modal>

            {{-- Edit & Delete modals --}}
            <div id="houses-scroll-modals">@include('scroll-lists.houses-modals')</div>
        </div>
    </div>

    @include('partials.live-search')
</x-app-layout>
