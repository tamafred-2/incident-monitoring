<?php

namespace App\Http\Controllers;

use App\Enums\ActiveStatus;
use App\Enums\VisitorRequestStatus;
use App\Enums\VisitorStatus;
use App\Models\House;
use App\Models\ExpectedVisit;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\Visitor;
use App\Models\VisitorProfile;
use App\Models\VisitorRequest;
use App\Rules\PersonName;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitorController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);
        $filterDateFrom = $this->normalizeDateTimeInput($request->query('date_from'));
        $filterDateTo = $this->normalizeDateTimeInput($request->query('date_to'));
        $historyPerPage = 10;
        $checkOutPerPage = 10;

        $visitors = $this->buildVisitorHistoryQuery($request)
            ->orderByDesc('visitor_id')->simplePaginate($historyPerPage, ['*'], 'history_page')
            ->withQueryString();
        if ($request->header('X-Scroll-List')) {
            $subdivisions = $housesBySubdivision = $residentsByHouse = collect();
        } else {
            $subdivisions = $user->isAdmin()
                ? Subdivision::where('status', ActiveStatus::Active)->orderBy('subdivision_name')->get()
                : collect();
            $housesBySubdivision = House::query()
                ->select('house_id', 'subdivision_id', 'street', 'block', 'lot')
                ->orderBy('street')
                ->orderBy('block')
                ->orderBy('lot')
                ->get()
                ->groupBy('subdivision_id')
                ->map(fn ($houses) => $houses->map(fn (House $house) => [
                    'house_id' => (int) $house->house_id,
                    'street' => trim((string) $house->street),
                    'block' => trim((string) $house->block),
                    'lot' => trim((string) $house->lot),
                    'display_address' => $house->display_address,
                ])->values()->all());

            $residentsByHouse = House::query()
                ->when(! $user->isAdmin(), fn ($q) => $q->where('subdivision_id', $user->allowedSubdivisionId()))
                ->with(['residents' => fn ($q) => $q->where('status', ActiveStatus::Active)
                    ->select('house_id', 'resident_id', 'full_name', 'phone', 'relation_to_owner')])
                ->get()
                ->mapWithKeys(fn (House $house) => [
                    (string) $house->house_id => $house->residents->map(fn (Resident $r) => [
                        'id' => $r->resident_id,
                        'name' => $r->full_name,
                        'phone' => $r->phone,
                        'is_owner' => strcasecmp(trim((string) $r->relation_to_owner), 'Owner') === 0,
                    ])->values()->all(),
                ]);
        }
        $effectiveSubdivision = $this->resolveEffectiveSubdivisionId($request);

        $insideVisitorQuery = Visitor::query()
            ->with('subdivision')
            ->when(
                $filterQ !== '',
                function (Builder $builder) use ($filterQ) {
                    $builder->where(function (Builder $query) use ($filterQ) {
                        $query->where('surname', 'like', "%{$filterQ}%")
                            ->orWhere('first_name', 'like', "%{$filterQ}%")
                            ->orWhere('middle_initials', 'like', "%{$filterQ}%")
                            ->orWhere('extension', 'like', "%{$filterQ}%")
                            ->orWhere('phone', 'like', "%{$filterQ}%")
                            ->orWhere('purpose', 'like', "%{$filterQ}%")
                            ->orWhere('host_employee', 'like', "%{$filterQ}%")
                            ->orWhere('house_address_or_unit', 'like', "%{$filterQ}%")
                            ->orWhere('status', 'like', "%{$filterQ}%");
                    });
                }
            )
            ->when(
                ! $user->isAdmin(),
                fn ($builder) => $builder->where('subdivision_id', $user->allowedSubdivisionId())
            )
            ->when(
                $user->isAdmin() && $filterSubdivision,
                fn ($builder) => $builder->where('subdivision_id', $filterSubdivision)
            )
            ->where('status', VisitorStatus::Inside);
        $this->applyDateTimeFilters(
            $insideVisitorQuery,
            'check_in',
            $filterDateFrom,
            $filterDateTo
        );
        $insideVisitors = $insideVisitorQuery
            ->orderByDesc('check_in')
            ->orderByDesc('visitor_id')->simplePaginate($checkOutPerPage, ['*'], 'check_out_page')
            ->withQueryString();

        $expectedVisit = null;
        if ($request->filled('expected_visit')) {
            $expectedVisit = ExpectedVisit::with('resident.house')->findOrFail($request->integer('expected_visit'));
            abort_unless($user->canAccessSubdivision($expectedVisit->subdivision_id), 403);
            abort_unless($expectedVisit->status === 'Expected', 422, 'This visit is no longer expected.');
        }
        $selectedProfile = null;
        $profileId = (int) $request->old('visitor_profile_id', $expectedVisit?->visitor_profile_id ?? $request->query('profile', 0));
        if ($profileId) {
            $profile = VisitorProfile::findOrFail($profileId);
            abort_unless($user->canAccessSubdivision($profile->subdivision_id), 403);
            $profile->loadExists(['visits as is_inside' => fn ($query) => $query->where('status', VisitorStatus::Inside)]);
            $selectedProfile = app(VisitorProfileController::class)->format($profile);
        }

        return $this->scrollView('visitors.index', compact(
            'visitors',
            'selectedProfile',
            'expectedVisit',
            'subdivisions',
            'filterQ',
            'filterSubdivision',
            'effectiveSubdivision',
            'insideVisitors',
            'housesBySubdivision',
            'residentsByHouse',
            'historyPerPage',
            'checkOutPerPage',
            'filterDateFrom',
            'filterDateTo',
        ), ['visitor-history' => 'visitors', 'visitor-inside' => 'insideVisitors']);
    }

    public function show(Request $request, Visitor $visitor): View
    {
        $visitor->load('subdivision');
        $displayHouseAddress = $visitor->house_address_or_unit;

        if (filled($displayHouseAddress)) {
            $normalizedAddress = strtoupper(trim((string) $displayHouseAddress));

            $matchedHouse = House::query()
                ->where('subdivision_id', $visitor->subdivision_id)
                ->get()
                ->first(function (House $house) use ($normalizedAddress) {
                    $displayAddress = strtoupper($house->display_address);
                    $legacyAddress = strtoupper(House::formatAddress($house->block, $house->lot));

                    return $displayAddress === $normalizedAddress
                        || $legacyAddress === $normalizedAddress;
                });

            if ($matchedHouse) {
                $displayHouseAddress = $matchedHouse->display_address;
            }
        }

        return view('visitors.show', [
            'visitor' => $visitor,
            'displayHouseAddress' => $displayHouseAddress,
            'dashboardQuery' => $request->only(['inside_per_page', 'page']),
        ]);
    }

    public function reportSummary(Request $request): array
    {
        $query = $this->buildVisitorHistoryQuery($request);
        return ['count' => (clone $query)->count(), 'max_id' => (clone $query)->reorder()->max('visitor_id') ?? 0];
    }

    public function export(Request $request): Response|StreamedResponse|RedirectResponse
    {
        \App\Support\ReportBatch::validate($request);
        $format = strtolower((string) $request->query('format', 'excel'));
        if (! in_array($format, ['excel', 'pdf'], true)) {
            return back()->with('error', 'Unsupported export format.');
        }

        if ($format === 'pdf') {
            try {
                $pdf = Pdf::loadView('visitors.report-pdf', $this->visitorReportViewData($request, false, true, true))
                    ->setPaper('a4', 'portrait');

                return $pdf->download('visitor-report-'.now()->format('Ymd_His').\App\Support\ReportBatch::suffix($request).'.pdf');
            } catch (\Throwable $exception) {
                report($exception);

                return back()->with('error', 'PDF export failed on this server. Please try again.');
            }
        }

        $visitors = \App\Support\ReportBatch::apply($this->buildVisitorHistoryQuery($request)->orderByDesc('visitor_id'), $request)->get();
        $reportRows = $this->buildVisitorReportRows($visitors, false);
        $filename = 'visitor-report-'.now()->format('Ymd_His').\App\Support\ReportBatch::suffix($request).'.csv';

        return response()->streamDownload(function () use ($reportRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Phone', 'Visit Type', 'Purpose', 'Resident / Host', 'House / Unit', 'Vehicle Type', 'Vehicle Color', 'Plate Number', 'Check In', 'Check Out', 'Duration', 'Status', 'ID Photo URL']);

            foreach ($reportRows as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['phone'],
                    $row['visit_type'],
                    $row['purpose'],
                    $row['host'],
                    $row['house_unit'],
                    $row['vehicle_type'],
                    $row['vehicle_color'],
                    $row['plate_number'],
                    $row['check_in'],
                    $row['check_out'],
                    $row['duration'],
                    $row['status'],
                    $row['photo_url'] ?: '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request): View
    {
        \App\Support\ReportBatch::validate($request);
        return view('visitors.report-print', $this->visitorReportViewData(
            $request,
            $request->boolean('autoprint'),
            true
        ));
    }

    public function edit(Request $request, Visitor $visitor): View
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        return view('visitors.edit', [
            'visitor' => $visitor->load('subdivision'),
            'indexContext' => $this->visitorRouteContext($request, $visitor->subdivision_id),
        ]);
    }

    public function update(Request $request, Visitor $visitor): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'surname' => ['required', 'string', 'max:100', new PersonName],
            'first_name' => ['required', 'string', 'max:100', new PersonName],
            'middle_initials' => ['nullable', 'string', 'max:20', new PersonName],
            'extension' => ['nullable', 'string', 'max:20', new PersonName],
            'phone' => ['required', 'string', 'digits:11'],
            'purpose' => ['nullable', 'string'],
            'host_employee' => ['nullable', 'string', 'max:150', new PersonName],
            'house_address_or_unit' => ['nullable', 'string', 'max:120'],
            'check_in' => ['required', 'date'],
            'check_out' => ['nullable', 'date', 'after_or_equal:check_in'],
            'status' => ['required', Rule::enum(VisitorStatus::class)],
        ]);

        if ($data['status'] === VisitorStatus::Inside->value) {
            $data['check_out'] = null;
        }

        $visitor->update([
            'surname' => $data['surname'],
            'first_name' => $data['first_name'],
            'middle_initials' => $data['middle_initials'] ?? null,
            'extension' => $data['extension'] ?? null,
            'phone' => $data['phone'],
            'purpose' => $data['purpose'] ?? null,
            'host_employee' => filled($data['host_employee'] ?? null) ? trim((string) $data['host_employee']) : null,
            'house_address_or_unit' => filled($data['house_address_or_unit'] ?? null) ? trim((string) $data['house_address_or_unit']) : null,
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'] ?? null,
            'status' => $data['status'],
        ]);

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $visitor->subdivision_id))
            ->with('success', 'Visitor history record updated successfully.');
    }

    public function idPhoto(Request $request, Visitor $visitor): BinaryFileResponse
    {
        if (! $request->user()->canAccessSubdivision($visitor->subdivision_id)) {
            abort(403);
        }

        $absolutePath = $this->visitorPhotoAbsolutePath($visitor->id_photo_path);
        abort_unless($absolutePath !== null, 404);

        return response()->file($absolutePath);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['visitor_profile_id' => ['nullable', 'integer'], 'expected_visit_id' => ['nullable', 'integer']]);
        return DB::transaction(function () use ($request): RedirectResponse {
            if ($request->filled('expected_visit_id')) {
                $expected = ExpectedVisit::with('resident.house')->lockForUpdate()->findOrFail($request->integer('expected_visit_id'));
                abort_unless($request->user()->canAccessSubdivision($expected->subdivision_id), 403);
                if ($expected->status !== 'Expected') {
                    throw ValidationException::withMessages(['expected_visit_id' => 'This visit has already been checked in or cancelled.']);
                }
                if (! $expected->resident || $expected->resident->status !== ActiveStatus::Active || ! $expected->resident->house
                    || $expected->resident->subdivision_id != $expected->subdivision_id) {
                    throw ValidationException::withMessages(['expected_visit_id' => 'The scheduled resident is no longer available. Cancel this expected visit and record a new one.']);
                }
                if ($request->filled('visitor_profile_id') && $request->integer('visitor_profile_id') !== (int) $expected->visitor_profile_id) {
                    throw ValidationException::withMessages(['visitor_profile_id' => 'Select the visitor attached to this expected visit.']);
                }
                if ($request->filled('resident_id') && $request->integer('resident_id') !== (int) $expected->resident_id) {
                    throw ValidationException::withMessages(['resident_id' => 'This expected visit belongs to a different resident. Cancel it and schedule a new visit to change the host.']);
                }
                $request->merge([
                    'purpose' => $request->input('purpose', $expected->purpose),
                    'visitor_profile_id' => $expected->visitor_profile_id, 'visit_type' => 'resident',
                    'resident_id' => $expected->resident_id, 'resident_house_id' => $expected->resident->house_id,
                    'house_address_or_unit' => $expected->resident->house->display_address,
                ]);
                $request->attributes->set('expected_visit', $expected);
            }
            if ($request->filled('visitor_profile_id')) {
                $profile = VisitorProfile::lockForUpdate()->findOrFail($request->integer('visitor_profile_id'));
                abort_unless($request->user()->canAccessSubdivision($profile->subdivision_id), 403);
                if ($profile->visits()->where('status', VisitorStatus::Inside)->exists()) {
                    throw ValidationException::withMessages(['visitor_profile_id' => 'This visitor is already inside. Check out their current visit before adding another.']);
                }
                $request->merge($profile->only(['surname', 'first_name', 'middle_initials', 'extension', 'phone']));
                $request->merge(['subdivision_id' => $profile->subdivision_id]);
                $request->attributes->set('visitor_profile', $profile);
            }
            return $this->storeVisit($request);
        });
    }

    private function storeVisit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subdivision_id' => ['nullable', 'integer'],
            'visit_type' => ['required', Rule::in(['resident', 'walk_in'])],
            'surname' => ['required', 'string', 'max:100', new PersonName],
            'first_name' => ['required', 'string', 'max:100', new PersonName],
            'middle_initials' => ['nullable', 'string', 'max:20', new PersonName],
            'extension' => ['nullable', 'string', 'max:20', new PersonName],
            'phone' => ['required', 'string', 'digits:11'],
            'purpose' => ['nullable', 'string'],
            'on_vehicle' => ['nullable', 'boolean'],
            'plate_number' => ['nullable', 'string', 'max:30', 'required_if:on_vehicle,1'],
            'passenger_count' => ['nullable', 'integer', 'min:1', 'max:20', 'required_if:on_vehicle,1'],
            'vehicle_type' => ['nullable', 'string', 'max:30', 'required_if:on_vehicle,1'],
            'vehicle_color' => ['nullable', 'string', 'max:30', 'required_if:on_vehicle,1'],
            'id_photo' => [$request->attributes->get('visitor_profile')?->id_photo_path ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'host_employee' => ['nullable', 'string', 'max:150', new PersonName],
            'house_address_or_unit' => ['nullable', 'string', 'max:120', 'required_if:visit_type,resident', 'required_if:visit_type,walk_in'],
            'resident_house_id' => ['nullable', 'integer'],
            'resident_id' => ['nullable', 'integer', 'required_if:visit_type,resident'],
        ]);

        $subdivisionId = $this->resolveSubmittedSubdivisionId($request);
        if (! $subdivisionId) {
            return back()->withErrors(['subdivision_id' => 'Please select a valid subdivision.'])->withInput();
        }

        // Older check-in clients can still submit identity fields; link them to the saved person.
        if (! $request->attributes->get('visitor_profile')) {
            $name = Visitor::formatFullName($data['first_name'], $data['middle_initials'] ?? null, $data['surname'], $data['extension'] ?? null);
            $profile = VisitorProfile::where('subdivision_id', $subdivisionId)
                ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($name)])
                ->where('phone', $data['phone'])->lockForUpdate()->first();
            if ($profile) {
                if ($profile->visits()->where('status', VisitorStatus::Inside)->exists()) {
                    throw ValidationException::withMessages(['visitor_profile_id' => 'This visitor is already inside. Check out their current visit before adding another.']);
                }
                $request->attributes->set('visitor_profile', $profile);
            }
        }

        if (($data['visit_type'] ?? 'resident') === 'walk_in') {
            $idPhotoPath = $this->storeVisitorIdPhoto($request);
            $onVehicle = (bool) ($data['on_vehicle'] ?? false);
            $plateNumber = $onVehicle
                ? (trim((string) ($data['plate_number'] ?? '')) ?: null)
                : null;
            $passengerCount = $onVehicle
                ? (int) ($data['passenger_count'] ?? 0)
                : null;
            $vehicleType = $onVehicle
                ? (trim((string) ($data['vehicle_type'] ?? '')) ?: null)
                : null;
            $vehicleColor = $onVehicle
                ? (trim((string) ($data['vehicle_color'] ?? '')) ?: null)
                : null;

            Visitor::create([
                'visitor_profile_id' => $request->attributes->get('visitor_profile')?->getKey(),
                'subdivision_id' => $subdivisionId,
                'surname' => $data['surname'],
                'first_name' => $data['first_name'],
                'middle_initials' => $data['middle_initials'] ?? null,
                'extension' => $data['extension'] ?? null,
                'phone' => $data['phone'],
                'plate_number' => $plateNumber,
                'passenger_count' => $passengerCount,
                'vehicle_type' => $vehicleType,
                'vehicle_color' => $vehicleColor,
                'id_photo_path' => $idPhotoPath,
                'purpose' => $data['purpose'] ?? null,
                'host_employee' => null,
                'house_address_or_unit' => trim((string) ($data['house_address_or_unit'] ?? '')) ?: null,
                'check_in' => now(),
                'check_out' => null,
                'status' => VisitorStatus::Inside->value,
            ]);

            return redirect()->route('visitors.index', $this->visitorRouteContext($request, $subdivisionId))
                ->with('success', 'Walk-in visitor checked in successfully.');
        }

        $house = null;
        $residentHouseId = (int) ($data['resident_house_id'] ?? 0);
        if ($residentHouseId > 0) {
            $house = House::query()
                ->where('subdivision_id', $subdivisionId)
                ->where('house_id', $residentHouseId)
                ->first();
        }

        if (! $house) {
            $normalizedAddress = strtoupper(trim((string) ($data['house_address_or_unit'] ?? '')));

            $house = House::query()
                ->where('subdivision_id', $subdivisionId)
                ->get()
                ->first(function (House $candidate) use ($normalizedAddress) {
                    $displayAddress = strtoupper($candidate->display_address);
                    $legacyAddress = strtoupper(House::formatAddress($candidate->block, $candidate->lot));

                    return $displayAddress === $normalizedAddress
                        || $legacyAddress === $normalizedAddress;
                });
        }

        if (! $house) {
            return back()->withErrors([
                'house_address_or_unit' => 'Please select a valid house / unit for the chosen subdivision.',
            ])->withInput();
        }

        $resident = Resident::query()
            ->whereKey((int) $data['resident_id'])
            ->where('subdivision_id', $subdivisionId)
            ->where('house_id', $house->house_id)
            ->where('status', ActiveStatus::Active)
            ->first();

        if (! $resident) {
            return back()->withErrors([
                'resident_id' => 'Please select a valid active resident for the chosen house / unit.',
            ])->withInput();
        }

        $ownerApproval = null;
        // Record a fresh call outcome for every resident visit.
        $approval = $request->validate([
            'owner_permission' => ['required', 'boolean'],
            'approving_owner_id' => ['required', 'integer'],
        ]);

        $owner = Resident::query()
            ->whereKey($approval['approving_owner_id'])
            ->where('house_id', $house->house_id)
            ->where('subdivision_id', $subdivisionId)
            ->where('status', ActiveStatus::Active)
            ->first();

        if (! $owner || strcasecmp(trim((string) $owner->relation_to_owner), 'Owner') !== 0) {
            return back()->withErrors(['approving_owner_id' => 'Select an active owner of this house to record the call status.'])->withInput();
        }

        $ownerApproval = [
            'owner_id' => $owner->resident_id,
            'owner_name' => $owner->full_name,
            'owner_phone' => $owner->phone,
            'host_resident_id' => $resident->resident_id,
            'recorded_by' => $request->user()->user_id,
            'recorded_by_name' => $request->user()->full_name,
            'call_status' => $request->boolean('owner_permission') ? 'approved' : 'unavailable',
            'recorded_at' => now()->toIso8601String(),
            'approved_at' => $request->boolean('owner_permission') ? now()->toIso8601String() : null,
            'method' => $request->boolean('owner_permission') ? 'phone' : null,
        ];

        $idPhotoPath = $this->storeVisitorIdPhoto($request);
        $onVehicle = (bool) ($data['on_vehicle'] ?? false);
        $plateNumber = $onVehicle
            ? (trim((string) ($data['plate_number'] ?? '')) ?: null)
            : null;
        $passengerCount = $onVehicle
            ? (int) ($data['passenger_count'] ?? 0)
            : null;
        $vehicleType = $onVehicle
            ? (trim((string) ($data['vehicle_type'] ?? '')) ?: null)
            : null;
        $vehicleColor = $onVehicle
            ? (trim((string) ($data['vehicle_color'] ?? '')) ?: null)
            : null;

        $visitor = Visitor::create([
            'visitor_profile_id' => $request->attributes->get('visitor_profile')?->getKey(),
            'subdivision_id' => $subdivisionId,
            'surname' => $data['surname'],
            'first_name' => $data['first_name'],
            'middle_initials' => $data['middle_initials'] ?? null,
            'extension' => $data['extension'] ?? null,
            'phone' => $data['phone'],
            'plate_number' => $plateNumber,
            'passenger_count' => $passengerCount,
            'vehicle_type' => $vehicleType,
            'vehicle_color' => $vehicleColor,
            'id_photo_path' => $idPhotoPath,
            'purpose' => $data['purpose'] ?? null,
            'host_employee' => $resident->full_name,
            'owner_approval' => $ownerApproval,
            'house_address_or_unit' => $house->display_address,
            'check_in' => now(),
            'check_out' => null,
            'status' => VisitorStatus::Inside->value,
        ]);

        if ($expected = $request->attributes->get('expected_visit')) {
            $expected->update(['status' => 'Arrived', 'visitor_id' => $visitor->getKey()]);
        }

        VisitorRequest::create([
            'visitor_id' => $visitor->visitor_id,
            'resident_id' => $resident->resident_id,
            'subdivision_id' => $subdivisionId,
            'visitor_name' => Visitor::formatFullName(
                $data['first_name'],
                $data['middle_initials'] ?? null,
                $data['surname'],
                $data['extension'] ?? null
            ),
            'surname' => $data['surname'],
            'first_name' => $data['first_name'],
            'middle_initials' => $data['middle_initials'] ?? null,
            'extension' => $data['extension'] ?? null,
            'phone' => $data['phone'],
            'plate_number' => $plateNumber,
            'passenger_count' => $passengerCount,
            'vehicle_type' => $vehicleType,
            'vehicle_color' => $vehicleColor,
            'id_photo_path' => $idPhotoPath,
            'purpose' => $data['purpose'] ?? null,
            'house_address_or_unit' => $house->display_address,
            'status' => $request->boolean('owner_permission') ? VisitorRequestStatus::Approved->value : VisitorRequestStatus::Pending->value,
            'requested_at' => now(),
            'responded_at' => $request->boolean('owner_permission') ? now() : null,
        ]);

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $subdivisionId))
            ->with('success', 'Resident visit checked in successfully.');
    }

    public function checkout(Request $request, Visitor $visitor): RedirectResponse
    {
        if (! $request->user()->canAccessSubdivision($visitor->subdivision_id)) {
            return redirect()->route('visitors.index')->with('error', 'You cannot access that visitor record.');
        }

        if ($visitor->status !== VisitorStatus::Inside) {
            if ($request->input('return_to') === 'dashboard') {
                return redirect()->route('dashboard')->with('error', 'That visitor is already checked out.');
            }

            return redirect()->route('visitors.index', $this->visitorRouteContext($request, $visitor->subdivision_id))
                ->with('error', 'That visitor is already checked out.');
        }

        $visitor->update([
            'check_out' => now(),
            'status' => VisitorStatus::CheckedOut->value,
        ]);

        if ($request->input('return_to') === 'profile' && $visitor->visitor_profile_id) {
            return redirect()->route('visitor-profiles.show', $visitor->visitor_profile_id)->with('success', 'Visitor checked out successfully.');
        }

        return redirect()->route(
            $request->input('return_to') === 'dashboard' ? 'dashboard' : 'visitors.index',
            $request->input('return_to') === 'dashboard' ? [] : $this->visitorRouteContext($request, $visitor->subdivision_id)
        )->with('success', 'Visitor checked out successfully.');
    }

    public function destroy(Request $request, Visitor $visitor): RedirectResponse
    {
        if (! $request->user()->canAccessSubdivision($visitor->subdivision_id)) {
            return redirect()->route('visitors.index')->with('error', 'You cannot access that visitor record.');
        }

        $subdivisionId = $visitor->subdivision_id;
        $visitor->delete();

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $subdivisionId))
            ->with('success', 'Visitor archived successfully.');
    }

    public function restore(Request $request, int $visitorId): RedirectResponse
    {
        $visitor = Visitor::withTrashed()->findOrFail($visitorId);

        if (! $request->user()->canAccessSubdivision($visitor->subdivision_id)) {
            return redirect()->route('visitors.index')->with('error', 'You cannot access that visitor record.');
        }

        $visitor->restore();

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $visitor->subdivision_id))
            ->with('success', 'Visitor restored successfully.');
    }

    public function forceDelete(Request $request, int $visitorId): RedirectResponse
    {
        $visitor = Visitor::withTrashed()->findOrFail($visitorId);

        if (! $request->user()->canAccessSubdivision($visitor->subdivision_id)) {
            return redirect()->route('visitors.index')->with('error', 'You cannot access that visitor record.');
        }

        $subdivisionId = $visitor->subdivision_id;
        $visitor->forceDelete();

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $subdivisionId))
            ->with('success', 'Visitor permanently deleted.');
    }

    private function resolveEffectiveSubdivisionId(Request $request): ?int
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return $user->allowedSubdivisionId();
        }

        $requested = (int) $request->query('subdivision_id', 0);
        if ($requested > 0 && Subdivision::whereKey($requested)->exists()) {
            return $requested;
        }

        return Subdivision::where('status', ActiveStatus::Active)
            ->orderBy('subdivision_name')
            ->value('subdivision_id');
    }

    private function resolveSubmittedSubdivisionId(Request $request): ?int
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return $user->allowedSubdivisionId();
        }

        $requested = (int) $request->input('subdivision_id', 0);
        if ($requested < 1) {
            return null;
        }

        return Subdivision::whereKey($requested)->exists() ? $requested : null;
    }

    private function visitorRouteContext(Request $request, int|string|null $subdivisionId): array
    {
        $tab = $request->input('tab', $request->query('tab', 'history'));
        if (! in_array($tab, ['check-in', 'check-out', 'history'], true)) {
            $tab = 'history';
        }

        $context = [
            'tab' => $tab,
        ];

        $filterQ = trim((string) $request->input('q', $request->query('q', '')));
        if ($filterQ !== '') {
            $context['q'] = $filterQ;
        }
        $this->appendSharedFilterContext($context, $request);

        $historyPerPage = $this->resolvePerPageChoice(
            $request->input('history_per_page_custom', $request->query('history_per_page_custom')),
            $request->input('history_per_page', $request->query('history_per_page')),
            10
        );
        $checkOutPerPage = $this->resolvePerPageChoice(
            $request->input('check_out_per_page_custom', $request->query('check_out_per_page_custom')),
            $request->input('check_out_per_page', $request->query('check_out_per_page')),
            10
        );
        $context['history_per_page'] = $historyPerPage;
        $context['check_out_per_page'] = $checkOutPerPage;

        if ($request->user()->isAdmin() && $subdivisionId) {
            $context['subdivision_id'] = (int) $subdivisionId;
        }

        return $context;
    }

    private function appendSharedFilterContext(array &$context, Request $request): void
    {
        $dateFrom = $this->normalizeDateTimeInput($request->input('date_from', $request->query('date_from')));
        $dateTo = $this->normalizeDateTimeInput($request->input('date_to', $request->query('date_to')));

        if ($dateFrom !== null) {
            $context['date_from'] = $dateFrom;
        }
        if ($dateTo !== null) {
            $context['date_to'] = $dateTo;
        }
    }

    private function normalizeDateTimeInput(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return str_replace('T', ' ', $value).(strlen($value) === 16 ? ':00' : '');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
            return strlen($value) === 16 ? $value.':00' : $value;
        }

        return null;
    }

    private function applyDateTimeFilters(
        Builder $query,
        string $dateTimeColumn,
        ?string $dateFrom,
        ?string $dateTo
    ): void {
        if ($dateFrom !== null) {
            $query->where($dateTimeColumn, '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->where($dateTimeColumn, '<=', $dateTo);
        }
    }

    private function buildVisitorHistoryQuery(Request $request): Builder
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);
        $filterDateFrom = $this->normalizeDateTimeInput($request->query('date_from'));
        $filterDateTo = $this->normalizeDateTimeInput($request->query('date_to'));

        $query = Visitor::query()
            ->with('subdivision')
            ->when(! $request->boolean('chart_filter') && ! $request->boolean('report'), fn ($q) => $q->where('status', VisitorStatus::CheckedOut))
            ->orderByDesc('check_in');

        if ($request->has('report_max_id')) {
            $query->where('visitor_id', '<=', $request->integer('report_max_id'));
        }

        if ($filterQ !== '') {
            $query->where(function (Builder $builder) use ($filterQ) {
                $builder->where('surname', 'like', "%{$filterQ}%")
                    ->orWhere('first_name', 'like', "%{$filterQ}%")
                    ->orWhere('middle_initials', 'like', "%{$filterQ}%")
                    ->orWhere('extension', 'like', "%{$filterQ}%")
                    ->orWhere('phone', 'like', "%{$filterQ}%")
                    ->orWhere('purpose', 'like', "%{$filterQ}%")
                    ->orWhere('host_employee', 'like', "%{$filterQ}%")
                    ->orWhere('house_address_or_unit', 'like', "%{$filterQ}%")
                    ->orWhere('status', 'like', "%{$filterQ}%");
            });
        }

        $this->applyDateTimeFilters(
            $query,
            'check_in',
            $filterDateFrom,
            $filterDateTo
        );

        if (! $user->isAdmin()) {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        if ($request->filled('report_status')) {
            $request->validate(['report_status' => [Rule::in(['Inside', 'Checked Out'])]]);
            $query->where('status', $request->query('report_status'));
        }

        if ($request->boolean('chart_filter') && $request->filled('weekday')) {
            $weekday = $request->query('weekday');
            abort_unless(in_array($weekday, ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], true) && $filterDateFrom && $filterDateTo, 422);
            $dates = [];
            for ($day = Carbon::parse($filterDateFrom)->startOfDay(); $day->lte(Carbon::parse($filterDateTo)); $day->addDay()) {
                if ($day->format('D') === $weekday) {
                    $dates[] = $day->toDateString();
                }
            }
            $query->whereIn(DB::raw('DATE(check_in)'), $dates);
        }

        return $query;
    }

    private function visitorReportViewData(
        Request $request,
        bool $autoPrint = false,
        bool $includePhotos = true,
        bool $renderingPdf = false
    ): array {
        $query = $this->buildVisitorHistoryQuery($request)->orderByDesc('visitor_id');
        $totalMatching = $renderingPdf ? (clone $query)->count() : null;
        $visitors = $query->when($renderingPdf || $request->boolean('report'), fn ($query) => \App\Support\ReportBatch::apply($query, $request))->get();
        $reportRows = $this->buildVisitorReportRows($visitors, $includePhotos, $renderingPdf);

        return [
            'title' => 'Visitor Report',
            'generatedAt' => now(),
            'reportRows' => $reportRows,
            'totalMatching' => $totalMatching ?? count($reportRows),
            'autoPrint' => $autoPrint,
            'renderingPdf' => $renderingPdf,
            'reportQuery' => $request->query(),
            'previewMode' => $request->boolean('preview'),
        ];
    }

    private function buildVisitorReportRows($visitors, bool $includePhotos, bool $renderingPdf = false): array
    {
        return $visitors->map(function (Visitor $visitor) use ($includePhotos, $renderingPdf) {
            $photoUrl = $visitor->id_photo_path
                ? route('visitors.photo', ['visitor' => $visitor->visitor_id])
                : null;
            $photoDataUri = null;
            $photoPath = null;
            $photoOmitted = false;

            if ($includePhotos && $visitor->id_photo_path) {
                $absolutePath = $this->visitorPhotoAbsolutePath($visitor->id_photo_path);
                if ($absolutePath && File::exists($absolutePath)) {
                    if ($renderingPdf) {
                        if (\App\Support\PdfImage::supported($absolutePath)) {
                            $photoPath = $absolutePath;
                        } else {
                            $photoOmitted = true;
                        }
                    } else {
                        $mime = File::mimeType($absolutePath) ?: 'image/png';
                        $contents = File::get($absolutePath);
                        $photoDataUri = 'data:'.$mime.';base64,'.base64_encode($contents);
                    }
                }
            }

            return [
                'name' => $visitor->full_name,
                'phone' => $visitor->phone ?: '-',
                'visit_type' => filled($visitor->host_employee) ? 'Resident Visit' : 'Walk-in',
                'purpose' => $visitor->purpose ?: '-',
                'host' => $visitor->host_employee ?: 'Walk-in',
                'house_unit' => $visitor->house_address_or_unit ?: '-',
                'vehicle_type' => $visitor->vehicle_type ?: '-',
                'vehicle_color' => $visitor->vehicle_color ?: '-',
                'plate_number' => $visitor->plate_number ?: '-',
                'check_in' => $visitor->check_in?->format('Y-m-d h:i:s A') ?: '-',
                'check_out' => $visitor->check_out?->format('Y-m-d h:i:s A') ?: '-',
                'duration' => $visitor->visit_duration_label ?: '-',
                'status' => $visitor->status?->value ?? '-',
                'photo_url' => $photoUrl,
                'photo_data_uri' => $photoDataUri,
                'photo_path' => $photoPath,
                'photo_omitted' => $photoOmitted,
            ];
        })->all();
    }

    private function resolvePerPage(mixed $value, int $default = 10): int
    {
        $perPage = (int) $value;

        if ($perPage < 1) {
            return $default;
        }

        return min($perPage, 100);
    }

    private function resolvePerPageChoice(mixed $customValue, mixed $selectedValue, int $default = 10): int
    {
        $custom = (int) $customValue;
        if ($custom > 0) {
            return $this->resolvePerPage($custom, $default);
        }

        return $this->resolvePerPage($selectedValue, $default);
    }

    private function storeVisitorIdPhoto(Request $request): string
    {
        if (! $request->hasFile('id_photo')) {
            return $request->attributes->get('visitor_profile')->id_photo_path;
        }
        $file = $request->file('id_photo');
        if (! $file) {
            abort(422, 'ID photo is required.');
        }

        $path = $file->store('uploads/visitors/id-photos', 'public');
        $request->attributes->get('visitor_profile')?->update(['id_photo_path' => $path]);
        return $path;
    }

    private function visitorPhotoAbsolutePath(?string $path): ?string
    {
        if (! $path || ! str_starts_with($path, 'uploads/visitors/id-photos/')) {
            return null;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        $legacyPath = public_path($path);

        return File::exists($legacyPath) ? $legacyPath : null;
    }
}
