<x-modal name="report-incident" :show="$openReportModal || $errors->any()" maxWidth="2xl" focusable>
    <div class="bg-white p-6 sm:p-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Report Incident</h3>
                <p class="mt-1 text-sm text-slate-500">Submit a new incident report from inside the Laravel app.</p>
            </div>

            <button
                type="button"
                x-on:click="$dispatch('close')"
                class="rounded-lg px-2 py-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close report incident modal"
            >
                &times;
            </button>
        </div>

        <form method="POST" action="{{ route('incidents.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-4 md:grid-cols-2">
            @csrf
            @php
                $residentUser = auth()->user()?->isResident();
                $autoSubdivisionId = (int) old('subdivision_id', $effectiveSubdivision);
                $autoSubdivisionName = $reportSubdivisions->firstWhere('subdivision_id', $autoSubdivisionId)?->subdivision_name
                    ?? 'System subdivision';
                $selectedLocation = old('location');
                $houseLocations = $houses->pluck('display_address')->filter()->values();
                $isOtherLocation = filled($selectedLocation) && !$houseLocations->contains($selectedLocation);
                $locationSelectValue = $isOtherLocation ? '__other__' : ($selectedLocation ?? '');
            @endphp

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                <input type="hidden" name="subdivision_id" id="report_subdivision_id" value="{{ $autoSubdivisionId }}">
                <div class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                    {{ $autoSubdivisionName }}
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">House</label>
                <select name="house_id" id="report_house_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                    <option value="">Select house</option>
                    @foreach ($houses as $house)
                        <option value="{{ $house->house_id }}" @selected((int) old('house_id') === $house->house_id)>{{ $house->display_address }}</option>
                    @endforeach
                </select>
            </div>

            @if ($residentUser)
                <input type="hidden" name="reported_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                <input type="hidden" name="status" value="Open">
            @endif

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Description <span class="text-rose-500">*</span></label>
                <textarea name="description" rows="4" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('description') }}</textarea>
            </div>
            @php
                $selectedCategory = old('category');
                $customCategory = old('category_other');
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
                <label class="block text-sm font-medium text-slate-700">House <span class="text-rose-500">*</span></label>
                <select name="location" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" data-location-select>
                    <option value="">Select house</option>
                    @foreach ($houses as $house)
                        <option value="{{ $house->display_address }}" @selected($locationSelectValue === $house->display_address)>{{ $house->display_address }}</option>
                    @endforeach
                    <option value="__other__" @selected($locationSelectValue === '__other__')>Others</option>
                </select>
                <div class="@if ($locationSelectValue !== '__other__') hidden @endif mt-3" data-location-other-wrapper>
                    <input
                        type="text"
                        name="location_other"
                        value="{{ $isOtherLocation ? $selectedLocation : old('location_other') }}"
                        placeholder="Enter other location"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        data-location-other
                    >
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Incident Date & Time</label>
                <input type="datetime-local" name="incident_date" value="{{ old('incident_date', now()->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            @if (!$residentUser)
                <div>
                    <label class="block text-sm font-medium text-slate-700">Date Reported</label>
                    <input type="datetime-local" name="reported_at" value="{{ old('reported_at', now()->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Status</label>
                <select name="status" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" data-status-select>
                    @foreach (['Open', 'Under Investigation', 'Resolved', 'Closed'] as $status)
                        <option value="{{ $status }}" @selected(old('status', 'Open') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            @if (auth()->user()->isAdmin())
                <div>
                    <label class="block text-sm font-medium text-slate-700">Assign Staff (optional)</label>
                    <select name="assigned_to" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">Unassigned</option>
                        @foreach ($assignableStaff as $assignee)
                            <option value="{{ $assignee->user_id }}" @selected((int) old('assigned_to') === (int) $assignee->user_id)>
                                {{ $assignee->full_name }} - {{ ucfirst($assignee->role) }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif
            <div class="md:col-span-2 @if (!in_array(old('status', 'Open'), ['Resolved', 'Closed'], true)) hidden @endif" data-resolved-wrapper>
                <label class="block text-sm font-medium text-slate-700">Date Resolved</label>
                <input type="datetime-local" name="resolved_at" value="{{ old('resolved_at') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" data-resolved-input>
                </div>
            @endif
            <div class="md:col-span-2" data-proof-preview-root>
                <label class="block text-sm font-medium text-slate-700">Proof Photos <span class="text-rose-500">*</span></label>
                <input
                    type="file"
                    name="proof_photos[]"
                    accept="image/*"
                    multiple
                    required
                    class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm"
                    data-proof-input
                >
                <p class="mt-2 text-xs text-slate-500" data-proof-help>At least 1 image required. Up to 10. Accepted: JPG, PNG, WEBP, GIF. Max 5 MB each.</p>
                <div class="mt-4 hidden grid gap-3 sm:grid-cols-2 lg:grid-cols-4" data-proof-preview-list></div>
            </div>

            <div class="md:col-span-2 flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Submit Report</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            if (window.__incidentReportModalInitialized) {
                return;
            }

            window.__incidentReportModalInitialized = true;

            function initializeProofPhotoPreviews() {
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
            }

            initializeProofPhotoPreviews();

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

            function initializeLocationFields() {
                var locationRoots = document.querySelectorAll('[data-location-root]');

                locationRoots.forEach(function (root) {
                    if (root.dataset.locationInitialized === 'true') {
                        return;
                    }

                    root.dataset.locationInitialized = 'true';

                    var select = root.querySelector('[data-location-select]');
                    var otherWrapper = root.querySelector('[data-location-other-wrapper]');
                    var otherInput = root.querySelector('[data-location-other]');

                    if (!select || !otherWrapper || !otherInput) {
                        return;
                    }

                    function syncLocationField() {
                        var isOther = select.value === '__other__';
                        otherWrapper.classList.toggle('hidden', !isOther);

                        if (isOther) {
                            otherInput.setAttribute('required', 'required');
                            return;
                        }

                        otherInput.removeAttribute('required');
                        otherInput.value = '';
                    }

                    select.addEventListener('change', syncLocationField);
                    syncLocationField();
                });
            }

            initializeLocationFields();

            function initializeResolvedDateFields() {
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
            }

            initializeResolvedDateFields();

            // Reload houses when subdivision changes
            var subdivisionSelect = document.getElementById('report_subdivision_id');
            var houseSelect = document.getElementById('report_house_id');

            if (subdivisionSelect && houseSelect) {
                // Subdivision is now fixed to the system subdivision for modal reporting.
            }
        })();
    </script>
</x-modal>
