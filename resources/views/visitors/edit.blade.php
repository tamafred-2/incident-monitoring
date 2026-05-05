<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Visitor History</h2>
                <p class="mt-1 text-sm text-slate-500">Admin-only update for visitor history details.</p>
            </div>
            <a
                href="{{ route('visitors.index', $indexContext) }}"
                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Back to Visitor History
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('visitors.update', array_merge(['visitor' => $visitor->visitor_id], $indexContext)) }}" class="grid gap-4 md:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Surname</label>
                        <input type="text" name="surname" value="{{ old('surname', $visitor->surname) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $visitor->first_name) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Middle Initials</label>
                        <input type="text" name="middle_initials" value="{{ old('middle_initials', $visitor->middle_initials) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Extension</label>
                        <input type="text" name="extension" value="{{ old('extension', $visitor->extension) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $visitor->phone) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Status</label>
                        <select name="status" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="Inside" @selected(old('status', $visitor->status) === 'Inside')>Inside</option>
                            <option value="Checked Out" @selected(old('status', $visitor->status) === 'Checked Out')>Checked Out</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Purpose</label>
                        <textarea name="purpose" rows="3" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('purpose', $visitor->purpose) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Resident / Host</label>
                        <input type="text" name="host_employee" value="{{ old('host_employee', $visitor->host_employee) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">House / Unit</label>
                        <input type="text" name="house_address_or_unit" value="{{ old('house_address_or_unit', $visitor->house_address_or_unit) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Check In</label>
                        <input type="datetime-local" name="check_in" value="{{ old('check_in', optional($visitor->check_in)->format('Y-m-d\\TH:i')) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Check Out</label>
                        <input type="datetime-local" name="check_out" value="{{ old('check_out', optional($visitor->check_out)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>

                    <div class="md:col-span-2 flex flex-wrap justify-end gap-3">
                        <a href="{{ route('visitors.index', $indexContext) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                        <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
