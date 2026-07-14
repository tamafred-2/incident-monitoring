<x-app-layout>
    @php
        $incidentCategories = [
            'Security',
            'Safety',
            'Property Damage',
            'Theft',
            'Vandalism',
            'Noise Complaint',
            'Parking',
            'Suspicious Activity',
            'Medical',
            'Other',
        ];
    @endphp

    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Report Incident</h2>
            <p class="mt-1 text-sm text-slate-500">Submit an incident manually or through the system, then move it from pending to resolved.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('incidents.store') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
                    @csrf

                    @if ($effectiveSubdivision)
                        <input type="hidden" name="subdivision_id" value="{{ $effectiveSubdivision }}">
                    @endif

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea name="description" rows="4" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description') }}</textarea>
                    </div>
                    @php
                        $selectedCategory = old('category');
                        $customCategory = old('category_other');
                        $incidentStatusOptions = [
                            'Open' => 'Pending (Open)',
                            'Under Investigation' => 'Pending (Investigating)',
                        ];
                    @endphp
                    <div data-category-root>
                        <label class="block text-sm font-medium text-slate-700">Category</label>
                        <select name="category" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" data-category-select>
                            <option value="">Select category</option>
                            @foreach ($incidentCategories as $category)
                                <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                        <div class="@if ($selectedCategory !== 'Other') hidden @endif mt-3" data-category-other-wrapper>
                            <input
                                type="text"
                                name="category_other"
                                value="{{ $customCategory }}"
                                placeholder="Enter custom category"
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                data-category-other
                            >
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Location</label>
                        <input type="text" name="location" value="{{ old('location') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Incident Date & Time</label>
                        <input type="datetime-local" name="incident_date" value="{{ old('incident_date', now()->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Date Reported</label>
                        <input type="datetime-local" name="reported_at" value="{{ old('reported_at', now()->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" data-status-select>
                            @foreach ($incidentStatusOptions as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" @selected(old('status', 'Open') === $statusValue)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2 flex flex-wrap justify-end gap-3">
                        <a href="{{ route('incidents.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                        <button class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            function initializeCustomCategories() {
                var categoryRoots = document.querySelectorAll('[data-category-root]');

                categoryRoots.forEach(function (root) {
                    if (root.dataset.categoryInitialized === 'true') {
                        return;
                    }

                    root.dataset.categoryInitialized = 'true';

                    var select = root.querySelector('[data-category-select]');
                    var otherWrapper = root.querySelector('[data-category-other-wrapper]');
                    var otherInput = root.querySelector('[data-category-other]');

                    if (!select || !otherWrapper || !otherInput) {
                        return;
                    }

                    function syncCategoryField() {
                        var isOther = select.value === 'Other';
                        otherWrapper.classList.toggle('hidden', !isOther);

                        if (isOther) {
                            otherInput.setAttribute('required', 'required');
                            return;
                        }

                        otherInput.removeAttribute('required');
                        otherInput.value = '';
                    }

                    select.addEventListener('change', syncCategoryField);
                    syncCategoryField();
                });
            }

            initializeCustomCategories();

        })();
    </script>
</x-app-layout>
