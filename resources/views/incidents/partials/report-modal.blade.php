<x-modal name="report-incident" :show="$openReportModal || $errors->any()" maxWidth="2xl" focusable>
    <div class="bg-white p-6 sm:p-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Report Incident</h3>
                <p class="mt-1 text-sm text-slate-500">Submit an incident manually or through the system, then track status from pending to resolved.</p>
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
                $selectedHouseId = (int) old('house_id', 0);
                $selectedHouse = $selectedHouseId > 0
                    ? $houses->firstWhere('house_id', $selectedHouseId)
                    : null;
                $selectedLocation = old('location', '');
                $locationStreet = old('location_street', $selectedHouse?->street ?? '');
                $locationBlock = old('location_block', $selectedHouse?->block ?? '');
                $locationLot = old('location_lot', $selectedHouse?->lot ?? '');
                $houseOptions = $houses
                    ->map(function ($house) {
                        $street = trim((string) $house->street);
                        $block = trim((string) $house->block);
                        $lot = trim((string) $house->lot);

                        return [
                            'house_id' => (int) $house->house_id,
                            'street' => $street,
                            'block' => $block,
                            'lot' => $lot,
                            'location' => "Street: {$street} | Block: {$block} | Lot: {$lot}",
                        ];
                    })
                    ->values();
            @endphp

            <input type="hidden" name="subdivision_id" id="report_subdivision_id" value="{{ $autoSubdivisionId }}">
            <input type="hidden" name="status" value="Open">

            @if ($residentUser)
                <input type="hidden" name="reported_at" value="{{ now()->format('Y-m-d\TH:i') }}">
            @endif

            @php
                $selectedCategory = old('category');
                $customCategory = old('category_other');
            @endphp
            <div class="md:col-span-2" data-category-root>
                <label class="block text-sm font-medium text-slate-700">Category <span class="text-rose-500">*</span></label>
                <select name="category" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" data-category-select>
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
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Description <span class="text-rose-500">*</span></label>
                <textarea name="description" rows="4" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('description') }}</textarea>
            </div>
            <div
                class="md:col-span-2"
                data-location-root
                data-houses='@json($houseOptions)'
                data-selected-street="{{ $locationStreet }}"
                data-selected-block="{{ $locationBlock }}"
                data-selected-lot="{{ $locationLot }}"
            >
                <label class="block text-sm font-medium text-slate-700">Incident Location <span class="text-rose-500">*</span></label>
                <input type="hidden" name="house_id" value="{{ $selectedHouseId > 0 ? $selectedHouseId : '' }}" id="report_house_id" data-location-house-id>
                <input type="hidden" name="location" value="{{ $selectedLocation }}" data-location-value>
                <div class="mt-1 grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Street</label>
                        <select
                            name="location_street"
                            required
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            data-location-street
                        >
                            <option value="">Select street</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Block</label>
                        <select
                            name="location_block"
                            required
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                            data-location-block
                            disabled
                        >
                            <option value="">Select block</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Lot</label>
                        <select
                            name="location_lot"
                            required
                            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                            data-location-lot
                            disabled
                        >
                            <option value="">Select lot</option>
                        </select>
                    </div>
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
            @endif
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

                    var streetInput = root.querySelector('[data-location-street]');
                    var blockInput = root.querySelector('[data-location-block]');
                    var lotInput = root.querySelector('[data-location-lot]');
                    var houseIdInput = root.querySelector('[data-location-house-id]');
                    var locationValueInput = root.querySelector('[data-location-value]');
                    var initialStreet = (root.dataset.selectedStreet || '').trim();
                    var initialBlock = (root.dataset.selectedBlock || '').trim();
                    var initialLot = (root.dataset.selectedLot || '').trim();
                    var houses = [];

                    try {
                        houses = JSON.parse(root.dataset.houses || '[]');
                    } catch (error) {
                        houses = [];
                    }

                    if (!streetInput || !blockInput || !lotInput || !houseIdInput || !locationValueInput) {
                        return;
                    }

                    houses = houses
                        .map(function (house) {
                            return {
                                house_id: String(house.house_id || '').trim(),
                                street: String(house.street || '').trim(),
                                block: String(house.block || '').trim(),
                                lot: String(house.lot || '').trim(),
                                location: String(house.location || '').trim()
                            };
                        })
                        .filter(function (house) {
                            return house.house_id !== ''
                                && house.street !== ''
                                && house.block !== ''
                                && house.lot !== '';
                        });

                    function uniqueValues(items, key) {
                        var seen = {};
                        var values = [];

                        items.forEach(function (item) {
                            var value = String(item[key] || '').trim();
                            if (value === '' || seen[value]) {
                                return;
                            }

                            seen[value] = true;
                            values.push(value);
                        });

                        return values;
                    }

                    function setSelectOptions(select, values, placeholder, selectedValue) {
                        select.innerHTML = '';

                        var placeholderOption = document.createElement('option');
                        placeholderOption.value = '';
                        placeholderOption.textContent = placeholder;
                        select.appendChild(placeholderOption);

                        values.forEach(function (value) {
                            var option = document.createElement('option');
                            option.value = value;
                            option.textContent = value;
                            select.appendChild(option);
                        });

                        if (selectedValue && values.indexOf(selectedValue) !== -1) {
                            select.value = selectedValue;
                            return;
                        }

                        select.value = '';
                    }

                    function setSelectDisabled(select, isDisabled) {
                        if (isDisabled) {
                            select.setAttribute('disabled', 'disabled');
                            select.value = '';
                            return;
                        }

                        select.removeAttribute('disabled');
                    }

                    function syncSelectedHouse() {
                        var selectedStreet = String(streetInput.value || '').trim();
                        var selectedBlock = String(blockInput.value || '').trim();
                        var selectedLot = String(lotInput.value || '').trim();
                        var selectedHouse = houses.find(function (house) {
                            return house.street === selectedStreet
                                && house.block === selectedBlock
                                && house.lot === selectedLot;
                        });

                        houseIdInput.value = selectedHouse ? selectedHouse.house_id : '';
                        locationValueInput.value = selectedHouse ? selectedHouse.location : '';
                    }

                    function syncLotOptions(selectedLot) {
                        var selectedStreet = String(streetInput.value || '').trim();
                        var selectedBlock = String(blockInput.value || '').trim();

                        if (!selectedStreet || !selectedBlock) {
                            setSelectOptions(lotInput, [], 'Select lot', '');
                            setSelectDisabled(lotInput, true);
                            syncSelectedHouse();
                            return;
                        }

                        var filteredHouses = houses.filter(function (house) {
                            return house.street === selectedStreet && house.block === selectedBlock;
                        });
                        var lotValues = uniqueValues(filteredHouses, 'lot');

                        setSelectOptions(lotInput, lotValues, 'Select lot', selectedLot);
                        setSelectDisabled(lotInput, lotValues.length === 0);
                        syncSelectedHouse();
                    }

                    function syncBlockOptions(selectedBlock, selectedLot) {
                        var selectedStreet = String(streetInput.value || '').trim();

                        if (!selectedStreet) {
                            setSelectOptions(blockInput, [], 'Select block', '');
                            setSelectDisabled(blockInput, true);
                            setSelectOptions(lotInput, [], 'Select lot', '');
                            setSelectDisabled(lotInput, true);
                            syncSelectedHouse();
                            return;
                        }

                        var filteredHouses = houses.filter(function (house) {
                            return house.street === selectedStreet;
                        });
                        var blockValues = uniqueValues(filteredHouses, 'block');

                        setSelectOptions(blockInput, blockValues, 'Select block', selectedBlock);
                        setSelectDisabled(blockInput, blockValues.length === 0);
                        syncLotOptions(selectedLot);
                    }

                    var streets = uniqueValues(houses, 'street');
                    setSelectOptions(streetInput, streets, 'Select street', initialStreet);
                    syncBlockOptions(initialBlock, initialLot);

                    streetInput.addEventListener('change', function () {
                        syncBlockOptions('', '');
                    });
                    blockInput.addEventListener('change', function () {
                        syncLotOptions('');
                    });
                    lotInput.addEventListener('change', syncSelectedHouse);
                });
            }

            initializeLocationFields();

            // Reload houses when subdivision changes
            var subdivisionSelect = document.getElementById('report_subdivision_id');
            var houseSelect = document.getElementById('report_house_id');

            if (subdivisionSelect && houseSelect) {
                // Subdivision is now fixed to the system subdivision for modal reporting.
            }
        })();
    </script>
</x-modal>
