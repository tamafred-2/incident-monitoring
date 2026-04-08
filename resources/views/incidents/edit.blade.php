<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Incident</h2>
                <p class="mt-1 text-sm text-slate-500">Update incident information and add more proof images when needed.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('incidents.show', array_merge(['incidentId' => $incident->incident_id], $indexContext)) }}"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Back to Details
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('incidents.update', ['incidentId' => $incident->incident_id] + $indexContext) }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
                    @csrf
                    @method('PUT')
                    @if (request()->filled('view'))
                        <input type="hidden" name="view" value="{{ request('view') }}">
                    @endif

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                        <select name="subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                            <option value="">Select subdivision</option>
                            @foreach ($subdivisions as $subdivision)
                                <option value="{{ $subdivision->subdivision_id }}" @selected((int) old('subdivision_id', $incident->subdivision_id) === $subdivision->subdivision_id)>{{ $subdivision->subdivision_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Title</label>
                        <input type="text" name="title" value="{{ old('title', $incident->title) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('description', $incident->description) }}</textarea>
                    </div>
                    @php
                        $selectedCategory = old('category', in_array($incident->category, $incidentCategories, true) ? $incident->category : ($incident->category ? 'Other' : ''));
                        $customCategory = old('category_other', in_array($incident->category, $incidentCategories, true) ? '' : $incident->category);
                    @endphp
                    <div data-category-root>
                        <label class="block text-sm font-medium text-slate-700">Category</label>
                        <select name="category" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" data-category-select>
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
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                data-category-other
                            >
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Location</label>
                        <input type="text" name="location" value="{{ old('location', $incident->location) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Incident Date & Time</label>
                        <input type="datetime-local" name="incident_date" value="{{ old('incident_date', optional($incident->incident_date)->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Date Reported</label>
                        <input type="datetime-local" name="reported_at" value="{{ old('reported_at', optional($incident->reported_at)->format('Y-m-d\TH:i') ?? optional($incident->created_at)->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" data-status-select>
                            @foreach (['Open', 'Under Investigation', 'Resolved', 'Closed'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $incident->status) === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 @if (!in_array(old('status', $incident->status), ['Resolved', 'Closed'], true)) hidden @endif" data-resolved-wrapper>
                        <label class="block text-sm font-medium text-slate-700">Date Resolved</label>
                        <input type="datetime-local" name="resolved_at" value="{{ old('resolved_at', optional($incident->resolved_at)->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" data-resolved-input>
                    </div>

                    <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Existing Proof Images</h3>
                        @if ($proofPhotos->isNotEmpty())
                            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($proofPhotos as $photo)
                                    <a
                                        href="{{ $photo['url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:shadow-md"
                                    >
                                        <img src="{{ $photo['url'] }}" alt="Existing proof image {{ $loop->iteration }}" class="h-40 w-full object-cover">
                                        <div class="px-4 py-3 text-sm font-medium text-slate-700">Proof image {{ $loop->iteration }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-3 text-sm text-slate-500">This incident does not have any proof images yet.</p>
                        @endif
                    </div>

                    <div class="md:col-span-2" data-proof-preview-root>
                        <label class="block text-sm font-medium text-slate-700">Add More Proof Photos</label>
                        <input
                            type="file"
                            name="proof_photos[]"
                            accept="image/*"
                            multiple
                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm"
                            data-proof-input
                        >
                        <p class="mt-2 text-xs text-slate-500" data-proof-help>New uploads will be added to the existing proof images. Up to 10 files per upload.</p>
                        <div class="mt-4 hidden grid gap-3 sm:grid-cols-2 lg:grid-cols-4" data-proof-preview-list></div>
                    </div>

                    <div class="md:col-span-2 flex flex-wrap justify-end gap-3">
                        <a href="{{ route('incidents.show', array_merge(['incidentId' => $incident->incident_id], $indexContext)) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                        <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
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

            var statusSelects = document.querySelectorAll('[data-status-select]');
            statusSelects.forEach(function (select) {
                var root = select.closest('form');
                var resolvedWrapper = root ? root.querySelector('[data-resolved-wrapper]') : null;
                var resolvedInput = root ? root.querySelector('[data-resolved-input]') : null;
                var reportedInput = root ? root.querySelector('[name="reported_at"]') : null;

                if (!resolvedWrapper || !resolvedInput) {
                    return;
                }

                function syncResolvedField() {
                    var isResolved = select.value === 'Resolved' || select.value === 'Closed';
                    resolvedWrapper.classList.toggle('hidden', !isResolved);

                    if (isResolved) {
                        if (!resolvedInput.value && reportedInput && reportedInput.value) {
                            resolvedInput.value = reportedInput.value;
                        }
                        return;
                    }

                    resolvedInput.value = '';
                }

                select.addEventListener('change', syncResolvedField);
                syncResolvedField();
            });

            var previewRoots = document.querySelectorAll('[data-proof-preview-root]');

            previewRoots.forEach(function (root) {
                if (root.dataset.previewInitialized === 'true') {
                    return;
                }

                root.dataset.previewInitialized = 'true';

                var input = root.querySelector('[data-proof-input]');
                var previewList = root.querySelector('[data-proof-preview-list]');
                var helpText = root.querySelector('[data-proof-help]');
                var defaultHelpText = helpText ? helpText.textContent : '';

                if (!input || !previewList || !helpText) {
                    return;
                }

                input.addEventListener('change', function () {
                    previewList.innerHTML = '';

                    if (!input.files || !input.files.length) {
                        previewList.classList.add('hidden');
                        helpText.textContent = defaultHelpText;
                        return;
                    }

                    previewList.classList.remove('hidden');
                    helpText.textContent = input.files.length + ' image(s) selected.';

                    Array.prototype.forEach.call(input.files, function (file, index) {
                        if (!file.type || file.type.indexOf('image/') !== 0) {
                            return;
                        }

                        var objectUrl = URL.createObjectURL(file);
                        var card = document.createElement('div');
                        card.className = 'overflow-hidden rounded-2xl border border-slate-200 bg-slate-50';
                        card.innerHTML =
                            '<img src="' + objectUrl + '" alt="Proof photo preview ' + (index + 1) + '" class="h-32 w-full object-cover">' +
                            '<div class="space-y-1 px-3 py-2 text-xs text-slate-600">' +
                                '<p class="truncate font-medium text-slate-700">' + file.name + '</p>' +
                                '<p>' + Math.max(1, Math.round(file.size / 1024)) + ' KB</p>' +
                            '</div>';

                        var image = card.querySelector('img');
                        image.addEventListener('load', function () {
                            URL.revokeObjectURL(objectUrl);
                        });

                        previewList.appendChild(card);
                    });
                });
            });
        })();
    </script>
</x-app-layout>
