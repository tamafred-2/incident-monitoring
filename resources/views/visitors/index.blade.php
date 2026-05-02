<x-app-layout>
    @php
        $defaultMonitoringTab = request()->query('tab');

        if (!in_array($defaultMonitoringTab, ['check-out', 'history'], true)) {
            $defaultMonitoringTab = 'check-out';
        }
    @endphp

    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Visitors</h2>
                <p class="mt-1 text-sm text-slate-500">Visitor approval follows the corrected flow: contact resident using the registered phone number or use the automated resident response request.</p>
            </div>

            <div
                x-data="{ reminderOpen: false }"
                class="relative shrink-0"
                @mouseenter="reminderOpen = true"
                @mouseleave="reminderOpen = false"
            >
                <button
                    type="button"
                    @click="reminderOpen = !reminderOpen"
                    @focus="reminderOpen = true"
                    @blur="reminderOpen = false"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-sky-500/30"
                    aria-label="Visitor approval reminder"
                >
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10A8 8 0 114 4.223a8 8 0 0114 5.777zM9 8a1 1 0 112 0v4a1 1 0 11-2 0V8zm1-3a1.25 1.25 0 100 2.5A1.25 1.25 0 0010 5z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div
                    x-cloak
                    x-show="reminderOpen"
                    x-transition.origin.top.right.duration.150ms
                    class="absolute right-0 z-30 mt-2 w-96 max-w-[calc(100vw-2rem)] rounded-2xl border border-sky-200 bg-white p-4 shadow-xl"
                >
                    <p class="text-sm font-semibold text-sky-800">Visitor Approval Reminder</p>
                    <p class="mt-2 text-sm leading-6 text-sky-700">
                        For resident visits, Admin/Guard should contact the resident using the phone number registered in the system and record the resident response before allowing entry.
                        If automated approval is active, wait for the system response and apply the same allow or deny decision.
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div
            x-data="{
                activeMonitoringTab: @js($defaultMonitoringTab),
                housesBySubdivision: {{ \Illuminate\Support\Js::from($housesBySubdivision) }},
                residentsByHouse: {{ \Illuminate\Support\Js::from($residentsByHouse) }},
                selectedSubdivision: '{{ $effectiveSubdivision }}',
                selectedHouse: @js(old('house_address_or_unit', '')),
                selectedResidentId: @js(old('resident_id', '')),
                hostEmployee: @js(old('host_employee', '')),
                hostSearch: @js(old('host_employee', '')),
                selectedResidentPhone: '',
                hostOpen: false,
                get availableHouses() {
                    return this.housesBySubdivision[this.selectedSubdivision] || [];
                },
                get availableResidents() {
                    const residents = this.residentsByHouse[this.selectedHouse] || [];
                    if (!this.hostSearch.trim()) return residents;
                    const query = this.hostSearch.toLowerCase();
                    return residents.filter(r =>
                        r.name.toLowerCase().includes(query) ||
                        (r.phone || '').toLowerCase().includes(query)
                    );
                },
                selectResident(resident) {
                    this.selectedResidentId = resident.id;
                    this.hostEmployee = resident.name;
                    this.hostSearch = resident.name;
                    this.selectedResidentPhone = resident.phone || '';
                    this.hostOpen = false;
                },
                onHouseChange() {
                    this.selectedResidentId = '';
                    this.hostEmployee = '';
                    this.hostSearch = '';
                    this.selectedResidentPhone = '';
                }
            }"
            class="flex flex-col gap-6 px-4 mx-auto max-w-7xl sm:px-6 lg:px-8"
        >
            @include('partials.alerts')

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <form method="GET" action="{{ route('visitors.index') }}" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="tab" :value="activeMonitoringTab">
                    <input type="hidden" name="history_per_page" value="{{ $historyPerPage }}">
                    <input type="hidden" name="check_out_per_page" value="{{ $checkOutPerPage }}">
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-slate-700">Search</label>
                        <input
                            type="search"
                            name="q"
                            value="{{ $filterQ }}"
                            placeholder="Name, phone, resident, house, status"
                            class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-sky-600 hover:bg-sky-700">Apply</button>
                        <a
                            href="{{ route('visitors.index', ['history_per_page' => $historyPerPage, 'check_out_per_page' => $checkOutPerPage]) }}"
                            class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                        >
                            Clear
                        </a>
                        @if (auth()->user()->hasRole('security'))
                            <button
                                type="button"
                                x-on:click="$dispatch('open-modal', 'visitor-check-in')"
                                class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800"
                            >
                                Submit Visitor Request
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            @if (auth()->user()->hasRole('security'))
                <x-modal name="visitor-check-in" :show="$errors->any()" maxWidth="4xl" focusable>
                    <div class="p-6 bg-white sm:p-8">
                        <div class="flex items-center justify-between gap-4 mb-5">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Submit Visitor Request</h3>
                                <p class="mt-1 text-sm text-slate-500">Record identity and visit details, then wait for resident approval through registered phone contact or automated response.</p>
                            </div>
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="p-2 transition border rounded-xl border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700"
                                aria-label="Close visitor check-in modal"
                            >
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                    <form method="POST" action="{{ route('visitors.store') }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="tab" value="check-in">
                        <input type="hidden" name="q" value="{{ $filterQ }}">
                        <input type="hidden" name="history_per_page" value="{{ $historyPerPage }}">
                        <input type="hidden" name="check_out_per_page" value="{{ $checkOutPerPage }}">

                        <input type="hidden" name="subdivision_id" value="{{ $effectiveSubdivision }}">
                        <input type="hidden" name="visit_type" value="resident">

                        <div class="p-5 border rounded-2xl border-sky-200 bg-sky-50/60">
                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-800">Approval Step</h4>
                            <p class="mt-2 text-sm leading-6 text-sky-700">
                                After submission, the resident response must be recorded first. Only approved requests should proceed to visitor entry.
                            </p>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visitor Identity</h4>
                                <p class="mt-1 text-sm text-slate-500">Enter the visitor's name exactly as shown on their valid ID.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1.2fr_1.2fr_0.7fr_0.7fr]">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Surname</label>
                                    <input type="text" name="surname" value="{{ old('surname') }}" required class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">First Name</label>
                                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Middle Initials</label>
                                    <input type="text" name="middle_initials" value="{{ old('middle_initials') }}" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500" placeholder="M.I.">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Extension</label>
                                    <input type="text" name="extension" value="{{ old('extension') }}" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500" placeholder="Jr.">
                                </div>
                            </div>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Contact</h4>
                                <p class="mt-1 text-sm text-slate-500">Visitor phone number is required for records and follow-up.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Phone</label>
                                    <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700">Purpose</label>
                                    <textarea name="purpose" rows="2" class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">{{ old('purpose') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 bg-white border rounded-2xl border-slate-200">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Resident Visit Details</h4>
                                <p class="mt-1 text-sm text-slate-500">Select the house and resident being visited for approval tracking.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">House / Unit</label>
                                    <select name="house_address_or_unit" x-model="selectedHouse" x-on:change="onHouseChange()" required class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                        <option value="">Select house / unit</option>
                                        <template x-for="house in availableHouses" :key="house">
                                            <option :value="house" x-text="house"></option>
                                        </template>
                                    </select>
                                </div>
                                <div x-data class="relative">
                                    <label class="block text-sm font-medium text-slate-700">Resident</label>
                                    <input type="hidden" name="resident_id" :value="selectedResidentId">
                                    <input type="hidden" name="host_employee" :value="hostEmployee">
                                    <input
                                        type="text"
                                        x-model="hostSearch"
                                        x-on:input="hostOpen = true; selectedResidentId = ''; hostEmployee = hostSearch; selectedResidentPhone = ''"
                                        x-on:focus="hostOpen = availableResidents.length > 0"
                                        x-on:click.away="hostOpen = false"
                                        :placeholder="selectedHouse ? 'Type to search residents...' : 'Select a house first'"
                                        :disabled="!selectedHouse"
                                        required
                                        autocomplete="off"
                                        class="w-full mt-1 text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-100 disabled:text-slate-400"
                                    >
                                    <p class="mt-2 text-xs text-slate-500">Select a resident from the suggestion list so the request reaches the correct registered phone number.</p>
                                    <p
                                        x-cloak
                                        x-show="selectedResidentPhone"
                                        class="mt-1 text-xs font-semibold text-sky-700"
                                        x-text="'Resident contact: ' + selectedResidentPhone"
                                    ></p>
                                    <ul
                                        x-cloak
                                        x-show="hostOpen && availableResidents.length > 0"
                                        class="absolute z-20 w-full mt-1 overflow-hidden text-sm bg-white border shadow-lg rounded-xl border-slate-200"
                                    >
                                        <template x-for="resident in availableResidents" :key="resident.id">
                                            <li
                                                x-on:mousedown.prevent="selectResident(resident)"
                                                class="cursor-pointer px-4 py-2.5 hover:bg-sky-50"
                                            >
                                                <p class="font-medium text-slate-800" x-text="resident.name"></p>
                                                <p class="text-xs text-slate-500" x-text="resident.phone || 'No phone on file'"></p>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-end gap-3">
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-sky-600 hover:bg-sky-700">Submit Request</button>
                        </div>
                    </form>
                    </div>
                </x-modal>
            @endif

            <div class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200">
                <nav class="flex flex-wrap gap-6 px-2 pb-1 border-b border-slate-200" aria-label="Visitor monitoring sections">
                    <a
                        href="#visitor-check-out"
                        @click.prevent="activeMonitoringTab = 'check-out'"
                        :class="activeMonitoringTab === 'check-out' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-1 pb-3 text-sm font-semibold transition border-b-2"
                    >
                        Visitor Check-out
                    </a>
                    <a
                        href="#visitor-history"
                        @click.prevent="activeMonitoringTab = 'history'"
                        :class="activeMonitoringTab === 'history' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-1 pb-3 text-sm font-semibold transition border-b-2"
                    >
                        Visitor History
                    </a>
                </nav>
            </div>

            <div>
                <section
                    id="visitor-check-out"
                    x-cloak
                    x-show="activeMonitoringTab === 'check-out'"
                    x-transition.opacity.duration.150ms
                    class="min-w-0 scroll-mt-24"
                >
                    <div class="h-full overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Visitor Check-out</h3>
                                    <p class="mt-1 text-sm text-slate-500">Visitors already approved and currently inside can be checked out here.</p>
                                </div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Name</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Resident / Host</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                        <th class="px-6 py-3 font-semibold text-left text-slate-600">Checked In</th>
                                        @if (auth()->user()->hasRole('security'))
                                            <th class="px-6 py-3 font-semibold text-left text-slate-600">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    @forelse ($insideVisitors as $visitor)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <div class="min-w-[12rem]">
                                                    <div class="font-medium text-slate-900">{{ $visitor->full_name }}</div>
                                                    <div class="mt-1 text-xs text-slate-500">{{ $visitor->phone ?: 'No phone provided' }}</div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600">
                                                <div class="max-w-[14rem] truncate" title="{{ $visitor->host_employee ?: '-' }}">
                                                    {{ $visitor->host_employee ?: '-' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600">
                                                <div class="max-w-[12rem] truncate" title="{{ $visitor->house_address_or_unit ?: '-' }}">
                                                    {{ $visitor->house_address_or_unit ?: '-' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600">
                                                @if ($visitor->check_in)
                                                    <div class="min-w-[9rem]">
                                                        <div class="whitespace-nowrap font-medium text-slate-700">{{ $visitor->check_in->format('M j, Y') }}</div>
                                                        <div class="mt-1 whitespace-nowrap text-xs text-slate-500">{{ $visitor->check_in->format('h:i A') }}</div>
                                                    </div>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            @if (auth()->user()->hasRole('security'))
                                                <td class="px-6 py-4">
                                                    <form method="POST" action="{{ route('visitors.checkout', $visitor) }}">
                                                        @csrf
                                                        <input type="hidden" name="tab" value="check-out">
                                                        <input type="hidden" name="q" value="{{ $filterQ }}">
                                                        <input type="hidden" name="history_per_page" value="{{ $historyPerPage }}">
                                                        <input type="hidden" name="check_out_per_page" value="{{ $checkOutPerPage }}">
                                                        <button class="px-3 py-2 text-xs font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">Check Out</button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ auth()->user()->hasRole('security') ? 5 : 4 }}" class="px-6 py-10 text-center text-slate-500">No visitors are currently inside.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="flex flex-col gap-3 px-6 py-4 border-t border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-slate-500">
                                @if ($insideVisitors->total() > 0)
                                    Showing {{ $insideVisitors->firstItem() }}-{{ $insideVisitors->lastItem() }} of {{ $insideVisitors->total() }} visitors
                                @else
                                    No visitor records to paginate
                                @endif
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <form method="GET" action="{{ route('visitors.index') }}" class="flex items-center gap-2">
                                    @if ($filterQ !== '')
                                        <input type="hidden" name="q" value="{{ $filterQ }}">
                                    @endif
                                    @if ($filterSubdivision)
                                        <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                    @endif
                                    <input type="hidden" name="tab" value="check-out">
                                    <input type="hidden" name="history_per_page" value="{{ $historyPerPage }}">
                                    <label for="check-out-rows-per-page" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Rows</label>
                                    <input
                                        id="check-out-rows-per-page"
                                        type="text"
                                        name="check_out_per_page"
                                        list="check-out-row-size-options"
                                        value=""
                                        placeholder="{{ $checkOutPerPage }}"
                                        class="w-24 rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                        aria-label="Rows per page"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        pattern="[0-9]{1,3}"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3)"
                                        onchange="if (this.value.trim() !== '') { this.form.requestSubmit(); }"
                                        onkeydown="if (event.key === 'Enter') { event.preventDefault(); if (this.value.trim() !== '') { this.form.requestSubmit(); } }"
                                    >
                                    <datalist id="check-out-row-size-options">
                                        <option value="10"></option>
                                        <option value="25"></option>
                                        <option value="50"></option>
                                        <option value="100"></option>
                                    </datalist>
                                </form>
                                @if ($insideVisitors->onFirstPage())
                                    <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></span>
                                @else
                                    <a href="{{ $insideVisitors->previousPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></a>
                                @endif
                                <span class="px-3 py-2 text-sm font-semibold rounded-xl bg-slate-100 text-slate-700">
                                    Page {{ $insideVisitors->currentPage() }} of {{ max($insideVisitors->lastPage(), 1) }}
                                </span>
                                @if ($insideVisitors->hasMorePages())
                                    <a href="{{ $insideVisitors->nextPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></a>
                                @else
                                    <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></span>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="visitor-history"
                    x-cloak
                    x-show="activeMonitoringTab === 'history'"
                    x-transition.opacity.duration.150ms
                    class="min-w-0 scroll-mt-24"
                >
                    <div class="h-full overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
                    <div class="flex flex-col gap-4 px-6 py-4 border-b border-slate-200 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Visitor History</h3>
                            <p class="mt-1 text-sm text-slate-500">Browse visitor check-in/check-out records with host, purpose, and status history.</p>
                        </div>

                        @if ($filterQ !== '' || $filterSubdivision)
                            <a
                                href="{{ route('visitors.index', array_filter([
                                    'tab' => 'history',
                                    'history_per_page' => $historyPerPage,
                                    'check_out_per_page' => $checkOutPerPage,
                                ])) }}"
                                class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                            >
                                Clear Filters
                            </a>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Name</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Phone</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Purpose</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Resident / Host</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Check In</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Check Out</th>
                                    <th class="px-6 py-3 font-semibold text-left text-slate-600">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @forelse ($visitors as $visitor)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="min-w-[12rem]">
                                                <div class="font-medium text-slate-900">{{ $visitor->full_name }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $visitor->phone ?: 'No phone provided' }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <div class="whitespace-nowrap">{{ $visitor->phone ?: '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <div class="max-w-[16rem] break-words" title="{{ $visitor->purpose ?: '-' }}">
                                                {{ \Illuminate\Support\Str::limit($visitor->purpose ?: '-', 80) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <div class="max-w-[14rem] truncate" title="{{ $visitor->host_employee ?: '-' }}">
                                                {{ $visitor->host_employee ?: '-' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <div class="max-w-[12rem] truncate" title="{{ $visitor->house_address_or_unit ?: '-' }}">
                                                {{ $visitor->house_address_or_unit ?: '-' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            @if ($visitor->check_in)
                                                <div class="min-w-[9rem]">
                                                    <div class="whitespace-nowrap font-medium text-slate-700">{{ $visitor->check_in->format('M j, Y') }}</div>
                                                    <div class="mt-1 whitespace-nowrap text-xs text-slate-500">{{ $visitor->check_in->format('h:i A') }}</div>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            @if ($visitor->check_out)
                                                <div class="min-w-[9rem]">
                                                    <div class="whitespace-nowrap font-medium text-slate-700">{{ $visitor->check_out->format('M j, Y') }}</div>
                                                    <div class="mt-1 whitespace-nowrap text-xs text-slate-500">{{ $visitor->check_out->format('h:i A') }}</div>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <span class="inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold {{ $visitor->status === 'Inside' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                                {{ $visitor->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-slate-500">No visitors found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col gap-3 px-6 py-4 border-t border-slate-200 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-500">
                            @if ($visitors->total() > 0)
                                Showing {{ $visitors->firstItem() }}-{{ $visitors->lastItem() }} of {{ $visitors->total() }} visitor records
                            @else
                                No visitor records to paginate
                            @endif
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="GET" action="{{ route('visitors.index') }}" class="flex items-center gap-2">
                                @if ($filterQ !== '')
                                    <input type="hidden" name="q" value="{{ $filterQ }}">
                                @endif
                                @if ($filterSubdivision)
                                    <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                @endif
                                <input type="hidden" name="tab" value="history">
                                <input type="hidden" name="check_out_per_page" value="{{ $checkOutPerPage }}">
                                <label for="history-rows-per-page" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Rows</label>
                                <input
                                    id="history-rows-per-page"
                                    type="text"
                                    name="history_per_page"
                                    list="history-row-size-options"
                                    value=""
                                    placeholder="{{ $historyPerPage }}"
                                    class="w-24 rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                    aria-label="Rows per page"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    pattern="[0-9]{1,3}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3)"
                                    onchange="if (this.value.trim() !== '') { this.form.requestSubmit(); }"
                                    onkeydown="if (event.key === 'Enter') { event.preventDefault(); if (this.value.trim() !== '') { this.form.requestSubmit(); } }"
                                >
                                <datalist id="history-row-size-options">
                                    <option value="10"></option>
                                    <option value="25"></option>
                                    <option value="50"></option>
                                    <option value="100"></option>
                                </datalist>
                            </form>
                            @if ($visitors->onFirstPage())
                                <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></span>
                            @else
                                <a href="{{ $visitors->previousPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></a>
                            @endif
                            <span class="px-3 py-2 text-sm font-semibold rounded-xl bg-slate-100 text-slate-700">
                                Page {{ $visitors->currentPage() }} of {{ max($visitors->lastPage(), 1) }}
                            </span>
                            @if ($visitors->hasMorePages())
                                <a href="{{ $visitors->nextPageUrl() }}" class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></a>
                            @else
                                <span class="px-3 py-2 text-sm font-semibold border rounded-xl border-slate-200 text-slate-400"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></span>
                            @endif
                        </div>
                    </div>
                    </div>
                </section>
            </div>


        </div>
    </div>
</x-app-layout>

