<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Residents" subtitle="Search residents by name and view their contact details." />
    </x-slot>

    <div class="page-section">
        <div class="page-shell flex flex-col gap-4 min-[1600px]:gap-6">
            @include('partials.alerts')

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <form method="GET" action="{{ route('residents.index') }}" class="grid gap-4 md:grid-cols-[1fr_180px_auto]">
                    @if (request()->filled('relation_to_owner'))
                        <input type="hidden" name="relation_to_owner" value="{{ request('relation_to_owner') }}">
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Search</label>
                        <input type="search" name="q" value="{{ $filterQ }}" placeholder="Name, address, house"
                               data-live-search="#residents-results" autocomplete="off"
                               class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                        <p class="mt-1 text-xs text-slate-400">Searches residents as you type</p>
                    </div>
                    @if (auth()->user()->isAdmin())
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Status</label>
                        <select name="status" onchange="this.form.requestSubmit()" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                            <option value="">All</option>
                            @foreach (\App\Enums\ActiveStatus::values() as $statusOption)
                                <option value="{{ $statusOption }}" @selected($filterStatus === $statusOption)>{{ $statusOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="flex items-start gap-3 md:pt-6">
                        @if (auth()->user()->isAdmin())
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', 'create-resident')"
                                class="whitespace-nowrap rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                            >
                                Add Resident
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div id="residents-results" class="flex flex-col gap-6">
            <div class="overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex flex-col gap-4 px-6 py-4 border-b border-slate-200 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Resident Directory</h3>
                        @if (request()->filled('relation_to_owner'))
                            <p class="mt-2 text-sm text-slate-600">Relation to owner: {{ request('relation_to_owner') }}
                                <a href="{{ route('residents.index', request()->except(['relation_to_owner', 'page'])) }}" class="ml-2 text-brand-700 underline">Clear filter</a>
                            </p>
                        @endif
                        <p class="mt-1 text-sm text-slate-500">Find contact details and housing assignments.</p>
                    </div>
                </div>
                <x-scroll-list name="residents" :records="$residents" modal-target="#residents-scroll-modals">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Name</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">House</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Street</th>
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Relation</th>
                                @if (auth()->user()->isAdmin())
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Status</th>
                                @endif
                                <th class="px-6 py-3 font-semibold text-left text-slate-600">Action</th>
                            </tr>
                        </thead>
                        <tbody data-scroll-rows id="residents-rows" class="bg-white divide-y divide-slate-100">
@include('scroll-lists.residents')
</tbody>
                    </table>
                </x-scroll-list>

            </div>

            @if (auth()->user()->isAdmin())
                <x-modal name="create-resident" :show="$errors->any() && old('edit_resident_id') === null" maxWidth="3xl" focusable>
                    <div class="p-6 bg-white sm:p-8" x-data x-on:open-modal.window="if ($event.detail === 'create-resident') { $nextTick(() => { $el.querySelectorAll('input:not([type=hidden]):not([type=checkbox])').forEach(i => i.value = ''); $el.querySelectorAll('input[type=checkbox]').forEach(i => i.checked = false); $el.querySelectorAll('textarea').forEach(t => t.value = ''); $el.querySelectorAll('select').forEach(s => s.selectedIndex = 0); }); }">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Add Resident</h3>
                                <p class="mt-1 text-sm text-slate-500">Create a resident profile and optionally assign a managed house.</p>
                            </div>
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="p-2 transition border rounded-xl border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700"
                                aria-label="Close create resident modal"
                            >
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('residents.store') }}" class="mt-6 space-y-4">
                            @csrf
                            @include('residents.partials.form-fields', [
                                'resident' => null,
                                'subdivisions' => $subdivisions,
                                'houses' => $houses,
                                'withAccount' => false,
                            ])

                            <div class="flex flex-wrap gap-3 pt-2">
                                <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800">
                                    Save Resident
                                </button>
                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </x-modal>
            @endif

            <div id="residents-scroll-modals">@include('scroll-lists.residents-modals', ['residents' => $modalResidents])</div>
            </div>{{-- /#residents-results --}}

        </div>
    </div>

    @include('partials.live-search')
</x-app-layout>
