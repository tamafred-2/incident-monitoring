<x-app-layout>
    @php
        $defaultVisitorTab = request()->query('tab');

        if (!in_array($defaultVisitorTab, ['check-in', 'check-out', 'history'], true)) {
            $defaultVisitorTab = auth()->user()->hasRole('security') ? 'check-in' : 'check-out';
        }

        if ($errors->any()) {
            $defaultVisitorTab = 'check-in';
        }
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Visitors</h2>
                <p class="mt-1 text-sm text-slate-500">Visitor check-in, check-out, and record cleanup now run inside the Laravel app.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div x-data="{ activeTab: '{{ $defaultVisitorTab }}' }" class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <nav class="flex flex-wrap gap-6 border-b border-slate-200 px-2 pb-1" aria-label="Visitor sections">
                    @if (auth()->user()->hasRole('security'))
                        <a
                            href="#visitor-check-in"
                            @click.prevent="activeTab = 'check-in'"
                            :class="activeTab === 'check-in' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                            class="border-b-2 px-1 pb-3 text-sm font-semibold transition"
                        >
                            Check In
                        </a>
                    @endif

                    <a
                        href="#visitor-check-out"
                        @click.prevent="activeTab = 'check-out'"
                        :class="activeTab === 'check-out' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-1 pb-3 text-sm font-semibold transition"
                    >
                        Check Out
                    </a>

                    <a
                        href="#visitor-history"
                        @click.prevent="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-1 pb-3 text-sm font-semibold transition"
                    >
                        Visitor History
                    </a>
                </nav>
            </div>

            @if ($subdivisions->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <form method="GET" action="{{ route('visitors.index') }}" class="flex flex-wrap items-end gap-4">
                        <input type="hidden" name="tab" :value="activeTab">
                        <input type="hidden" name="view" value="{{ $historyView }}">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Subdivision Filter</label>
                            <select
                                x-ref="subdivisionFilter"
                                name="subdivision_id"
                                class="mt-1 rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            >
                                <option value="">All subdivisions</option>
                                @foreach ($subdivisions as $subdivision)
                                    <option value="{{ $subdivision->subdivision_id }}" @selected($filterSubdivision === $subdivision->subdivision_id)>{{ $subdivision->subdivision_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Apply</button>
                        <button
                            type="button"
                            @click="$refs.subdivisionFilter.value = ''; $el.form.requestSubmit()"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Clear
                        </button>
                    </form>
                </div>
            @endif

            @if (auth()->user()->hasRole('security'))
                <div
                    id="visitor-check-in"
                    x-cloak
                    x-show="activeTab === 'check-in'"
                    x-transition.opacity.duration.150ms
                    class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Visitor Check-in</h3>
                            <p class="mt-1 text-sm text-slate-500">Log a new visitor using grouped sections for identity, contact, and visit details.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('visitors.store') }}" class="space-y-6">
                        @csrf
                        <input type="hidden" name="tab" value="check-in">

                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Access Details</h4>
                                <p class="mt-1 text-sm text-slate-500">Start with the subdivision where the visitor will enter.</p>
                            </div>

                            @if ($subdivisions->isNotEmpty())
                                <div class="max-w-md">
                                    <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                                    <select name="subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                                        <option value="">Select subdivision</option>
                                        @foreach ($subdivisions as $subdivision)
                                            <option value="{{ $subdivision->subdivision_id }}" @selected(old('subdivision_id', $effectiveSubdivision) == $subdivision->subdivision_id)>{{ $subdivision->subdivision_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif ($effectiveSubdivision)
                                <input type="hidden" name="subdivision_id" value="{{ $effectiveSubdivision }}">
                                <p class="text-sm text-slate-600">Subdivision is set automatically for your account.</p>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visitor Identity</h4>
                                <p class="mt-1 text-sm text-slate-500">Enter the visitor’s name exactly as shown on their valid ID.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1.2fr_1.2fr_0.7fr_0.7fr]">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Surname</label>
                                    <input type="text" name="surname" value="{{ old('surname') }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">First Name</label>
                                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Middle Initials</label>
                                    <input type="text" name="middle_initials" value="{{ old('middle_initials') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" placeholder="M.I.">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Extension</label>
                                    <input type="text" name="extension" value="{{ old('extension') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" placeholder="Jr.">
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Contact And ID</h4>
                                <p class="mt-1 text-sm text-slate-500">Optional details for identification and follow-up.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Phone</label>
                                    <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">ID Number</label>
                                    <input type="text" name="id_number" value="{{ old('id_number') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Company</label>
                                    <input type="text" name="company" value="{{ old('company') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Visit Details</h4>
                                <p class="mt-1 text-sm text-slate-500">Who they are visiting and the reason for entry.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Host / Employee</label>
                                    <input type="text" name="host_employee" value="{{ old('host_employee') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700">Purpose</label>
                                    <textarea name="purpose" rows="3" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('purpose') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-end gap-3">
                            <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Check In Visitor</button>
                        </div>
                    </form>
                </div>
            @endif

            <div
                id="visitor-check-out"
                x-cloak
                x-show="activeTab === 'check-out'"
                x-transition.opacity.duration.150ms
                class="scroll-mt-24"
            >
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Visitor Check-out</h3>
                        <p class="mt-1 text-sm text-slate-500">View visitors who are still inside and process their check-out from this section.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Name</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Company</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Host</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Checked In</th>
                                    @if ($subdivisions->isNotEmpty())
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Subdivision</th>
                                    @endif
                                    @if (auth()->user()->hasRole('security'))
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($insideVisitors as $visitor)
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $visitor->full_name }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->company ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->host_employee ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ optional($visitor->check_in)->format('M j, Y H:i') }}</td>
                                        @if ($subdivisions->isNotEmpty())
                                            <td class="px-6 py-4 text-slate-600">{{ $visitor->subdivision->subdivision_name ?? '-' }}</td>
                                        @endif
                                        @if (auth()->user()->hasRole('security'))
                                            <td class="px-6 py-4">
                                                <form method="POST" action="{{ route('visitors.checkout', $visitor) }}">
                                                    @csrf
                                                    <input type="hidden" name="tab" value="check-out">
                                                    <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Check Out</button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $subdivisions->isNotEmpty() ? (auth()->user()->hasRole('security') ? 6 : 5) : (auth()->user()->hasRole('security') ? 5 : 4) }}" class="px-6 py-10 text-center text-slate-500">No visitors are currently inside.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div
                id="visitor-history"
                x-cloak
                x-show="activeTab === 'history'"
                x-transition.opacity.duration.150ms
                class="scroll-mt-24"
            >
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Visitor History</h3>
                            <p class="mt-1 text-sm text-slate-500">Browse previous visitor records, statuses, and timestamps.</p>
                        </div>

                        <form method="GET" action="{{ route('visitors.index') }}" class="flex flex-wrap items-end gap-3">
                            @if ($filterSubdivision)
                                <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                            @endif
                            <input type="hidden" name="tab" value="history">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">View</label>
                                <select
                                    name="view"
                                    class="mt-1 rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                >
                                    <option value="active" @selected($historyView === 'active')>Active</option>
                                    <option value="deleted" @selected($historyView === 'deleted')>Deleted</option>
                                    <option value="all" @selected($historyView === 'all')>All</option>
                                </select>
                            </div>

                            <button
                                type="submit"
                                class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
                            >
                                Apply
                            </button>

                            <a
                                href="{{ route('visitors.index', array_filter([
                                    'tab' => 'history',
                                    'subdivision_id' => $filterSubdivision,
                                ], fn ($value) => filled($value))) }}"
                                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                Clear
                            </a>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Name</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Phone</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Company</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Purpose</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Host</th>
                                    @if ($subdivisions->isNotEmpty())
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Subdivision</th>
                                    @endif
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Check In</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Check Out</th>
                                    <th class="px-6 py-3 text-left font-semibold text-slate-600">Status</th>
                                    @if ($historyView !== 'active')
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Archived At</th>
                                    @endif
                                    @if (auth()->user()->hasRole('security'))
                                        <th class="px-6 py-3 text-left font-semibold text-slate-600">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($visitors as $visitor)
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $visitor->full_name }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->phone ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->company ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ \Illuminate\Support\Str::limit($visitor->purpose ?: '-', 40) }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->host_employee ?: '-' }}</td>
                                        @if ($subdivisions->isNotEmpty())
                                            <td class="px-6 py-4 text-slate-600">{{ $visitor->subdivision->subdivision_name ?? '-' }}</td>
                                        @endif
                                        <td class="px-6 py-4 text-slate-600">{{ optional($visitor->check_in)->format('M j, Y H:i') }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ optional($visitor->check_out)->format('M j, Y H:i') ?: '-' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $visitor->status }}</td>
                                        @if ($historyView !== 'active')
                                            <td class="px-6 py-4 text-slate-600">{{ optional($visitor->deleted_at)->format('M j, Y H:i') ?: '-' }}</td>
                                        @endif
                                        @if (auth()->user()->hasRole('security'))
                                            <td class="px-6 py-4">
                                                @if ($visitor->trashed())
                                                    <div class="flex flex-wrap gap-2">
                                                        <form method="POST" action="{{ route('visitors.restore', $visitor->visitor_id) }}">
                                                            @csrf
                                                            <input type="hidden" name="tab" value="history">
                                                            <input type="hidden" name="view" value="{{ $historyView }}">
                                                            <button class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Restore</button>
                                                        </form>
                                                        <button
                                                            type="button"
                                                            x-data
                                                            x-on:click="$dispatch('open-modal', 'force-delete-visitor-{{ $visitor->visitor_id }}')"
                                                            class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                                        >
                                                            Force Delete
                                                        </button>
                                                    </div>
                                                @else
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="$dispatch('open-modal', 'archive-visitor-{{ $visitor->visitor_id }}')"
                                                        class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                                    >
                                                        Archive
                                                    </button>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $subdivisions->isNotEmpty() ? (auth()->user()->hasRole('security') ? ($historyView !== 'active' ? 11 : 10) : ($historyView !== 'active' ? 10 : 9)) : (auth()->user()->hasRole('security') ? ($historyView !== 'active' ? 10 : 9) : ($historyView !== 'active' ? 9 : 8)) }}" class="px-6 py-10 text-center text-slate-500">No visitors found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @foreach ($visitors as $visitor)
                @if (auth()->user()->hasRole('security') && !$visitor->trashed())
                    <x-modal name="archive-visitor-{{ $visitor->visitor_id }}" maxWidth="md" focusable>
                        <div class="bg-white p-6 sm:p-8">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Archive Visitor?</h3>
                                    <p class="mt-2 text-sm text-slate-600">
                                        {{ $visitor->full_name }} will be removed from active history, but the record will stay available in the deleted view so it can still be restored later.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('visitors.destroy', $visitor) }}" class="mt-6 flex flex-wrap justify-end gap-3">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="tab" value="history">
                                <input type="hidden" name="view" value="{{ $historyView }}">

                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                                <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                                    Archive Visitor
                                </button>
                            </form>
                        </div>
                    </x-modal>
                @endif

                @if (auth()->user()->hasRole('security') && $visitor->trashed())
                    <x-modal name="force-delete-visitor-{{ $visitor->visitor_id }}" maxWidth="md" focusable>
                        <div class="bg-white p-6 sm:p-8">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Permanently Delete Visitor?</h3>
                                    <p class="mt-2 text-sm text-slate-600">
                                        This will permanently remove {{ $visitor->full_name }} from the database. This action cannot be undone.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('visitors.force-delete', $visitor->visitor_id) }}" class="mt-6 flex flex-wrap justify-end gap-3">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="tab" value="history">
                                <input type="hidden" name="view" value="{{ $historyView }}">

                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                                <button class="rounded-xl bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">
                                    Force Delete
                                </button>
                            </form>
                        </div>
                    </x-modal>
                @endif
            @endforeach
        </div>
    </div>
</x-app-layout>
