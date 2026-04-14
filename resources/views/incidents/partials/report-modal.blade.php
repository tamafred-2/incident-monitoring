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
            @endphp

            @if ($reportSubdivisions->isNotEmpty())
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Subdivision</label>
                    <select name="subdivision_id" id="report_subdivision_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                        <option value="">Select subdivision</option>
                        @foreach ($reportSubdivisions as $subdivision)
                            <option value="{{ $subdivision->subdivision_id }}" @selected((int) old('subdivision_id', auth()->user()->isAdmin() ? '' : ($filterSubdivision ?: $effectiveSubdivision)) === $subdivision->subdivision_id)>{{ $subdivision->subdivision_name }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif ($effectiveSubdivision)
                <input type="hidden" name="subdivision_id" id="report_subdivision_id" value="{{ $effectiveSubdivision }}">
            @endif

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">House</label>
                <select name="house_id" id="report_house_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                    <option value="">Select house</option>
                    @foreach ($houses as $house)
                        <option value="{{ $house->house_id }}" @selected((int) old('house_id') === $house->house_id)>{{ $house->display_address }}</option>
                    @endforeach
                </select>
            </div>

            @if (!$residentUser)
            <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-col gap-3">
                    <div>
                        <h4 class="text-base font-semibold text-slate-900">Verify Reporter</h4>
                        <p class="mt-1 text-sm text-slate-500">Optional: verify the reporting resident by code or QR before you submit the incident.</p>
                    </div>
                    <input type="hidden" name="verified_resident_id" id="verified_resident_id" value="{{ old('verified_resident_id') }}">
                    <input type="hidden" name="verification_method" id="verification_method" value="{{ old('verification_method') }}">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input type="text" id="resident_code_input" placeholder="Resident code" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:max-w-xs">
                        <button type="button" id="verify_code_btn" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Verify by code</button>
                        <button type="button" id="scan_qr_btn" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Scan QR</button>
                        <button type="button" id="clear_verify_btn" class="hidden rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Clear</button>
                    </div>
                    <p id="verified_display" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"></p>
                    <p id="verify_error" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"></p>
                </div>
            </div>
            @else
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
            <div>
                <label class="block text-sm font-medium text-slate-700">Location</label>
                <input type="text" name="location" value="{{ old('location') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
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

    <div id="scan_modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/70 px-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Scan resident QR code</h3>
                <button type="button" id="close_scan_modal" class="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700">&times;</button>
            </div>
            <div id="qr_reader" class="min-h-[260px]"></div>
            <p id="scan_status" class="mt-3 text-sm text-slate-500"></p>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
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

            var verifyUrl = @json(route('api.verify-resident'));
            var modal = document.getElementById('scan_modal');
            var qrReaderEl = document.getElementById('qr_reader');
            var verifyError = document.getElementById('verify_error');
            var verifiedDisplay = document.getElementById('verified_display');
            var residentIdInput = document.getElementById('verified_resident_id');
            var verificationMethodInput = document.getElementById('verification_method');
            var clearButton = document.getElementById('clear_verify_btn');
            var scanner = null;

            function getSubdivisionId() {
                var input = document.querySelector('[name="subdivision_id"]');
                return input ? input.value : '';
            }

            function showError(message) {
                verifyError.textContent = message;
                verifyError.classList.remove('hidden');
            }

            function hideError() {
                verifyError.textContent = '';
                verifyError.classList.add('hidden');
            }

            function setVerified(residentId, method, name, address) {
                residentIdInput.value = residentId;
                verificationMethodInput.value = method;
                verifiedDisplay.textContent = 'Verified: ' + name + (address ? ' - ' + address : '');
                verifiedDisplay.classList.remove('hidden');
                clearButton.classList.remove('hidden');
                hideError();
            }

            function clearVerified() {
                residentIdInput.value = '';
                verificationMethodInput.value = '';
                verifiedDisplay.textContent = '';
                verifiedDisplay.classList.add('hidden');
                clearButton.classList.add('hidden');
                hideError();
            }

            function verifyCode(code, method) {
                var subdivisionId = getSubdivisionId();
                if (!code || !subdivisionId) {
                    showError('Resident code and subdivision are required.');
                    return;
                }

                fetch(verifyUrl + '?code=' + encodeURIComponent(code) + '&subdivision_id=' + encodeURIComponent(subdivisionId), {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data.success) {
                            setVerified(data.resident_id, method, data.full_name, data.address_or_unit);
                            return;
                        }

                        showError(data.error || 'Verification failed.');
                    })
                    .catch(function () {
                        showError('Verification request failed.');
                    });
            }

            function stopScanner() {
                if (!scanner) {
                    return Promise.resolve();
                }

                return scanner.stop().catch(function () {}).then(function () {
                    scanner = null;
                    qrReaderEl.innerHTML = '';
                });
            }

            document.getElementById('verify_code_btn').addEventListener('click', function () {
                verifyCode(document.getElementById('resident_code_input').value.trim(), 'manual_code');
            });

            clearButton.addEventListener('click', clearVerified);

            document.getElementById('scan_qr_btn').addEventListener('click', function () {
                var subdivisionId = getSubdivisionId();
                if (!subdivisionId) {
                    showError('Please select a subdivision first.');
                    return;
                }

                hideError();
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.getElementById('scan_status').textContent = 'Starting camera...';

                if (typeof Html5Qrcode === 'undefined') {
                    document.getElementById('scan_status').textContent = 'QR scanner failed to load. Use manual code instead.';
                    return;
                }

                scanner = new Html5Qrcode('qr_reader');

                Html5Qrcode.getCameras()
                    .then(function (cameras) {
                        if (!cameras || !cameras.length) {
                            throw new Error('No camera found');
                        }

                        var cameraId = cameras[0].id;
                        for (var i = 0; i < cameras.length; i += 1) {
                            if (cameras[i].label && cameras[i].label.toLowerCase().indexOf('back') !== -1) {
                                cameraId = cameras[i].id;
                                break;
                            }
                        }

                        return scanner.start(
                            cameraId,
                            { fps: 10, qrbox: { width: 250, height: 250 } },
                            function (decodedText) {
                                var code = decodedText.indexOf('RESIDENT:') === 0 ? decodedText.slice(9) : decodedText;
                                document.getElementById('resident_code_input').value = code;
                                document.getElementById('scan_status').textContent = 'Verifying...';
                                stopScanner().then(function () {
                                    modal.classList.add('hidden');
                                    modal.classList.remove('flex');
                                    verifyCode(code, 'qr_scan');
                                });
                            },
                            function () {}
                        );
                    })
                    .then(function () {
                        document.getElementById('scan_status').textContent = 'Point the camera at the resident QR code.';
                    })
                    .catch(function () {
                        document.getElementById('scan_status').textContent = 'Could not start the camera. Use manual code instead.';
                    });
            });

            document.getElementById('close_scan_modal').addEventListener('click', function () {
                stopScanner().then(function () {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                });
            });

            modal.addEventListener('click', function (event) {
                if (event.target !== modal) {
                    return;
                }

                stopScanner().then(function () {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                });
            });

            if (residentIdInput.value && verificationMethodInput.value) {
                verifiedDisplay.textContent = 'Resident verification will be kept on submit.';
                verifiedDisplay.classList.remove('hidden');
                clearButton.classList.remove('hidden');
            }

            // Reload houses when subdivision changes
            var subdivisionSelect = document.getElementById('report_subdivision_id');
            var houseSelect = document.getElementById('report_house_id');

            if (subdivisionSelect && houseSelect) {
                subdivisionSelect.addEventListener('change', function () {
                    var subdivId = subdivisionSelect.value;
                    houseSelect.innerHTML = '<option value="">Loading...</option>';

                    if (!subdivId) {
                        houseSelect.innerHTML = '<option value="">Select house</option>';
                        return;
                    }

                    fetch('/api/houses-by-subdivision?subdivision_id=' + encodeURIComponent(subdivId), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            houseSelect.innerHTML = '<option value="">Select house</option>';
                            data.forEach(function (h) {
                                var opt = document.createElement('option');
                                opt.value = h.house_id;
                                opt.textContent = h.display_address;
                                houseSelect.appendChild(opt);
                            });
                        })
                        .catch(function () {
                            houseSelect.innerHTML = '<option value="">Failed to load houses</option>';
                        });
                });
            }
        })();
    </script>
</x-modal>
