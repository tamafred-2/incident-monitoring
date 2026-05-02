<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Incident</h2>
                <p class="mt-1 text-sm text-slate-500">Update incident information while keeping pending and resolved status tracking clear.</p>
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
        <div
            x-data="{
                previewImage: null,
                previewLabel: '',
                openPreview(url, label) {
                    this.previewImage = url;
                    this.previewLabel = label || 'Proof image preview';
                },
                closePreview() {
                    this.previewImage = null;
                    this.previewLabel = '';
                }
            }"
            class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8"
        >
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('incidents.update', ['incidentId' => $incident->incident_id] + $indexContext) }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
                    @csrf
                    @method('PUT')
                    @if (request()->filled('view'))
                        <input type="hidden" name="view" value="{{ request('view') }}">
                    @endif

                    <input type="hidden" name="subdivision_id" value="{{ $incident->subdivision_id }}">

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">House</label>
                        <select name="house_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" required data-house-select>
                            <option value="">Select house</option>
                            @foreach ($houses as $house)
                                <option value="{{ $house->house_id }}" data-address="{{ $house->display_address }}" @selected((int) old('house_id', $incident->house_id) === $house->house_id)>{{ $house->display_address }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Report ID</label>
                        <input type="text" value="{{ $incident->report_id }}" disabled class="mt-1 w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-500 shadow-sm">
                    </div>
                    @if (!$isFullEditor)
                        <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-sm text-slate-600">
                            Security mode: report details are shown for reference; saving here updates the incident status and resolved date.
                        </div>
                    @endif
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('description', $incident->description) }}</textarea>
                    </div>
                    @php
                        $selectedCategory = old('category', in_array($incident->category, $incidentCategories, true) ? $incident->category : ($incident->category ? 'Other' : ''));
                        $customCategory = old('category_other', in_array($incident->category, $incidentCategories, true) ? '' : $incident->category);
                        $removedProofPhotos = collect(old('remove_proof_photos', []))
                            ->filter(fn ($path) => is_string($path))
                            ->all();
                        $incidentStatusOptions = [
                            'Open' => 'Pending (Open)',
                            'Under Investigation' => 'Pending (Under Investigation)',
                            'Reported' => 'Pending (Reported)',
                            'Investigating' => 'Pending (Investigating)',
                            'Ongoing' => 'Pending (Ongoing)',
                            'Resolved' => 'Resolved',
                            'Closed' => 'Resolved (Closed)',
                        ];
                        $selectedHouseId = (int) old('house_id', $incident->house_id);
                        $selectedHouseAddress = optional($houses->firstWhere('house_id', $selectedHouseId))->display_address;
                        $effectiveLocation = old('location', $incident->location);
                        if (!filled($effectiveLocation)) {
                            $effectiveLocation = $selectedHouseAddress;
                        }

                        $isCustomLocation = filled($effectiveLocation) && filled($selectedHouseAddress) && $effectiveLocation !== $selectedHouseAddress;
                        $customLocationValue = $isCustomLocation ? old('location_other', $effectiveLocation) : old('location_other', '');
                        $locationInputValue = $isCustomLocation ? $customLocationValue : ($selectedHouseAddress ?? $effectiveLocation ?? '');
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
                    <div data-location-root>
                        <label class="block text-sm font-medium text-slate-700">Location</label>
                        <input type="hidden" name="location" value="{{ $locationInputValue }}" data-location-hidden required>
                        <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500" data-location-custom-toggle @checked($isCustomLocation)>
                            Use custom location instead of selected house
                        </label>
                        <div class="@if (!$isCustomLocation) hidden @endif mt-3" data-location-other-wrapper>
                            <input
                                type="text"
                                name="location_other"
                                value="{{ $customLocationValue }}"
                                placeholder="Enter other location"
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                data-location-other
                            >
                        </div>
                        <p class="mt-2 text-xs text-slate-500" data-location-summary>
                            Location follows the selected house unless custom location is enabled.
                        </p>
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
                            @foreach ($incidentStatusOptions as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" @selected(old('status', $incident->status) === $statusValue)>{{ $statusLabel }}</option>
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
                                    @php $markedForRemoval = in_array($photo['path'], $removedProofPhotos, true); @endphp
                                    <div class="overflow-hidden rounded-2xl border {{ $markedForRemoval ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200 bg-white' }}">
                                        <button
                                            type="button"
                                            @click="openPreview('{{ $photo['url'] }}', 'Existing proof image {{ $loop->iteration }}')"
                                            class="w-full transition hover:-translate-y-0.5 hover:shadow-md"
                                        >
                                            <img src="{{ $photo['url'] }}" alt="Existing proof image {{ $loop->iteration }}" class="h-40 w-full object-cover">
                                            <div class="px-4 py-3 text-sm font-medium text-slate-700">Proof image {{ $loop->iteration }}</div>
                                        </button>
                                        <label class="flex items-center gap-2 border-t border-slate-200 px-4 py-3 text-sm font-medium {{ $markedForRemoval ? 'text-rose-700' : 'text-slate-700' }}">
                                            <input
                                                type="checkbox"
                                                name="remove_proof_photos[]"
                                                value="{{ $photo['path'] }}"
                                                class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                                @checked($markedForRemoval)
                                            >
                                            Remove this image when saving
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-3 text-xs text-slate-500">Checked images will be deleted after you click Save Changes.</p>
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

            <div
                x-cloak
                x-show="previewImage"
                x-on:keydown.escape.window="closePreview()"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6"
                style="display: none;"
            >
                <div class="absolute inset-0" @click="closePreview()"></div>
                <div class="relative w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h3 class="text-base font-semibold text-slate-900" x-text="previewLabel || 'Proof image preview'"></h3>
                        <button
                            type="button"
                            @click="closePreview()"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Close
                        </button>
                    </div>
                    <div class="bg-slate-100 p-4">
                        <img :src="previewImage" :alt="previewLabel || 'Proof image preview'" class="max-h-[75vh] w-full rounded-2xl object-contain">
                    </div>
                </div>
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

            var locationRoots = document.querySelectorAll('[data-location-root]');
            locationRoots.forEach(function (root) {
                if (root.dataset.locationInitialized === 'true') {
                    return;
                }

                root.dataset.locationInitialized = 'true';

                var form = root.closest('form');
                var houseSelect = form ? form.querySelector('[data-house-select]') : null;
                var hiddenLocationInput = root.querySelector('[data-location-hidden]');
                var customToggle = root.querySelector('[data-location-custom-toggle]');
                var otherWrapper = root.querySelector('[data-location-other-wrapper]');
                var otherInput = root.querySelector('[data-location-other]');
                var summary = root.querySelector('[data-location-summary]');

                if (!houseSelect || !hiddenLocationInput || !customToggle || !otherWrapper || !otherInput || !summary) {
                    return;
                }

                function selectedHouseAddress() {
                    var selectedHouseOption = houseSelect.options[houseSelect.selectedIndex];
                    return selectedHouseOption ? (selectedHouseOption.getAttribute('data-address') || '') : '';
                }

                function syncLocationField() {
                    var usingCustom = customToggle.checked;
                    otherWrapper.classList.toggle('hidden', !usingCustom);
                    summary.classList.toggle('hidden', usingCustom);

                    if (usingCustom) {
                        otherInput.setAttribute('required', 'required');
                        hiddenLocationInput.value = otherInput.value.trim();
                        return;
                    }

                    otherInput.removeAttribute('required');
                    otherInput.value = '';
                    var houseAddress = selectedHouseAddress();
                    if (houseAddress) {
                        hiddenLocationInput.value = houseAddress;
                    }
                }

                customToggle.addEventListener('change', syncLocationField);
                otherInput.addEventListener('input', syncLocationField);
                houseSelect.addEventListener('change', syncLocationField);
                syncLocationField();
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
