<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\Visitor;
use App\Models\VisitorRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitorController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);
        $filterDateFrom = $this->normalizeDateTimeInput($request->query('date_from'));
        $filterDateTo = $this->normalizeDateTimeInput($request->query('date_to'));
        $historyPerPage = $this->resolvePerPageChoice(
            $request->query('history_per_page_custom'),
            $request->query('history_per_page'),
            10
        );
        $checkOutPerPage = $this->resolvePerPageChoice(
            $request->query('check_out_per_page_custom'),
            $request->query('check_out_per_page'),
            10
        );

        $visitors = $this->buildVisitorHistoryQuery($request)
            ->paginate($historyPerPage, ['*'], 'history_page')
            ->withQueryString();
        $subdivisions = $user->isAdmin()
            ? Subdivision::where('status', 'Active')->orderBy('subdivision_name')->get()
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
            ->with(['residents' => fn ($q) => $q->where('status', 'Active')
                ->select('house_id', 'resident_id', 'full_name', 'phone')])
            ->get()
            ->mapWithKeys(fn (House $house) => [
                (string) $house->house_id => $house->residents->map(fn (Resident $r) => [
                    'id'    => $r->resident_id,
                    'name'  => $r->full_name,
                    'phone' => $r->phone,
                ])->values()->all(),
            ]);
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
                !$user->isAdmin(),
                fn ($builder) => $builder->where('subdivision_id', $user->allowedSubdivisionId())
            )
            ->when(
                $user->isAdmin() && $filterSubdivision,
                fn ($builder) => $builder->where('subdivision_id', $filterSubdivision)
            )
            ->where('status', 'Inside');
        $this->applyDateTimeFilters(
            $insideVisitorQuery,
            'check_in',
            $filterDateFrom,
            $filterDateTo
        );
        $insideVisitors = $insideVisitorQuery
            ->orderByDesc('check_in')
            ->paginate($checkOutPerPage, ['*'], 'check_out_page')
            ->withQueryString();

        return view('visitors.index', compact(
            'visitors',
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
        ));
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

    public function export(Request $request): Response|StreamedResponse|RedirectResponse
    {
        $format = strtolower((string) $request->query('format', 'excel'));
        if (!in_array($format, ['excel', 'pdf'], true)) {
            return back()->with('error', 'Unsupported export format.');
        }

        if ($format === 'pdf') {
            try {
                $pdf = Pdf::loadView('visitors.report-print', $this->visitorReportViewData($request, false, true, true))
                    ->setPaper('a4', 'landscape');

                return $pdf->download('visitor-report-' . now()->format('Ymd_His') . '.pdf');
            } catch (\Throwable $exception) {
                report($exception);

                return back()->with('error', 'PDF export failed on this server. Please try again.');
            }
        }

        $visitors = $this->buildVisitorHistoryQuery($request)->get();
        $reportRows = $this->buildVisitorReportRows($visitors, false);
        $filename = 'visitor-report-' . now()->format('Ymd_His') . '.csv';

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
        return view('visitors.report-print', $this->visitorReportViewData(
            $request,
            $request->boolean('autoprint'),
            true
        ));
    }

    public function edit(Request $request, Visitor $visitor): View
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        return view('visitors.edit', [
            'visitor' => $visitor->load('subdivision'),
            'indexContext' => $this->visitorRouteContext($request, $visitor->subdivision_id),
        ]);
    }

    public function update(Request $request, Visitor $visitor): RedirectResponse
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_initials' => ['nullable', 'string', 'max:20'],
            'extension' => ['nullable', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9]+$/'],
            'purpose' => ['nullable', 'string'],
            'host_employee' => ['nullable', 'string', 'max:150'],
            'house_address_or_unit' => ['nullable', 'string', 'max:120'],
            'check_in' => ['required', 'date'],
            'check_out' => ['nullable', 'date', 'after_or_equal:check_in'],
            'status' => ['required', Rule::in(['Inside', 'Checked Out'])],
        ]);

        if ($data['status'] === 'Inside') {
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
        if (!$request->user()->canAccessSubdivision($visitor->subdivision_id)) {
            abort(403);
        }

        $absolutePath = $this->visitorPhotoAbsolutePath($visitor->id_photo_path);
        abort_unless($absolutePath !== null, 404);

        return response()->file($absolutePath);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subdivision_id'        => ['nullable', 'integer'],
            'visit_type'            => ['required', Rule::in(['resident', 'walk_in'])],
            'surname'               => ['required', 'string', 'max:100'],
            'first_name'            => ['required', 'string', 'max:100'],
            'middle_initials'       => ['nullable', 'string', 'max:20'],
            'extension'             => ['nullable', 'string', 'max:20'],
            'phone'                 => ['required', 'string', 'max:40', 'regex:/^[0-9]+$/'],
            'purpose'               => ['nullable', 'string'],
            'on_vehicle'            => ['nullable', 'boolean'],
            'plate_number'          => ['nullable', 'string', 'max:30', 'required_if:on_vehicle,1'],
            'passenger_count'       => ['nullable', 'integer', 'min:1', 'max:20', 'required_if:on_vehicle,1'],
            'vehicle_type'          => ['nullable', 'string', 'max:30', 'required_if:on_vehicle,1'],
            'vehicle_color'         => ['nullable', 'string', 'max:30', 'required_if:on_vehicle,1'],
            'id_photo'              => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'host_employee'         => ['nullable', 'string', 'max:150'],
            'house_address_or_unit' => ['nullable', 'string', 'max:120', 'required_if:visit_type,resident', 'required_if:visit_type,walk_in'],
            'resident_house_id'     => ['nullable', 'integer'],
            'resident_id'           => ['nullable', 'integer', 'required_if:visit_type,resident'],
        ]);

        $subdivisionId = $this->resolveSubmittedSubdivisionId($request);
        if (!$subdivisionId) {
            return back()->withErrors(['subdivision_id' => 'Please select a valid subdivision.'])->withInput();
        }

        $idPhotoPath = $this->storeVisitorIdPhoto($request);

        if (($data['visit_type'] ?? 'resident') === 'walk_in') {
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
                'subdivision_id'        => $subdivisionId,
                'surname'               => $data['surname'],
                'first_name'            => $data['first_name'],
                'middle_initials'       => $data['middle_initials'] ?? null,
                'extension'             => $data['extension'] ?? null,
                'phone'                 => $data['phone'],
                'plate_number'          => $plateNumber,
                'passenger_count'       => $passengerCount,
                'vehicle_type'          => $vehicleType,
                'vehicle_color'         => $vehicleColor,
                'id_photo_path'         => $idPhotoPath,
                'purpose'               => $data['purpose'] ?? null,
                'host_employee'         => null,
                'house_address_or_unit' => trim((string) ($data['house_address_or_unit'] ?? '')) ?: null,
                'check_in'              => now(),
                'check_out'             => null,
                'status'                => 'Inside',
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

        if (!$house) {
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

        if (!$house) {
            return back()->withErrors([
                'house_address_or_unit' => 'Please select a valid house / unit for the chosen subdivision.',
            ])->withInput();
        }

        $resident = Resident::query()
            ->whereKey((int) $data['resident_id'])
            ->where('subdivision_id', $subdivisionId)
            ->where('house_id', $house->house_id)
            ->where('status', 'Active')
            ->first();

        if (!$resident) {
            return back()->withErrors([
                'resident_id' => 'Please select a valid active resident for the chosen house / unit.',
            ])->withInput();
        }

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
            'subdivision_id'        => $subdivisionId,
            'surname'               => $data['surname'],
            'first_name'            => $data['first_name'],
            'middle_initials'       => $data['middle_initials'] ?? null,
            'extension'             => $data['extension'] ?? null,
            'phone'                 => $data['phone'],
            'plate_number'          => $plateNumber,
            'passenger_count'       => $passengerCount,
            'vehicle_type'          => $vehicleType,
            'vehicle_color'         => $vehicleColor,
            'id_photo_path'         => $idPhotoPath,
            'purpose'               => $data['purpose'] ?? null,
            'host_employee'         => $resident->full_name,
            'house_address_or_unit' => $house->display_address,
            'check_in'              => now(),
            'check_out'             => null,
            'status'                => 'Inside',
        ]);

        VisitorRequest::create([
            'visitor_id'            => $visitor->visitor_id,
            'resident_id'           => $resident->resident_id,
            'subdivision_id'        => $subdivisionId,
            'visitor_name'          => Visitor::formatFullName(
                $data['first_name'],
                $data['middle_initials'] ?? null,
                $data['surname'],
                $data['extension'] ?? null
            ),
            'surname'               => $data['surname'],
            'first_name'            => $data['first_name'],
            'middle_initials'       => $data['middle_initials'] ?? null,
            'extension'             => $data['extension'] ?? null,
            'phone'                 => $data['phone'],
            'plate_number'          => $plateNumber,
            'passenger_count'       => $passengerCount,
            'vehicle_type'          => $vehicleType,
            'vehicle_color'         => $vehicleColor,
            'id_photo_path'         => $idPhotoPath,
            'purpose'               => $data['purpose'] ?? null,
            'house_address_or_unit' => $house->display_address,
            'status'                => 'Approved',
            'requested_at'          => now(),
            'responded_at'          => now(),
        ]);

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $subdivisionId))
            ->with('success', 'Resident visit checked in successfully.');
    }

    public function checkout(Request $request, Visitor $visitor): RedirectResponse
    {
        if (!$request->user()->canAccessSubdivision($visitor->subdivision_id)) {
            return redirect()->route('visitors.index')->with('error', 'You cannot access that visitor record.');
        }

        if ($visitor->status !== 'Inside') {
            return redirect()->route('visitors.index', $this->visitorRouteContext($request, $visitor->subdivision_id))
                ->with('error', 'That visitor is already checked out.');
        }

        $visitor->update([
            'check_out' => now(),
            'status' => 'Checked Out',
        ]);

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $visitor->subdivision_id))
            ->with('success', 'Visitor checked out successfully.');
    }

    public function destroy(Request $request, Visitor $visitor): RedirectResponse
    {
        if (!$request->user()->canAccessSubdivision($visitor->subdivision_id)) {
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

        if (!$request->user()->canAccessSubdivision($visitor->subdivision_id)) {
            return redirect()->route('visitors.index')->with('error', 'You cannot access that visitor record.');
        }

        $visitor->restore();

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $visitor->subdivision_id))
            ->with('success', 'Visitor restored successfully.');
    }

    public function forceDelete(Request $request, int $visitorId): RedirectResponse
    {
        $visitor = Visitor::withTrashed()->findOrFail($visitorId);

        if (!$request->user()->canAccessSubdivision($visitor->subdivision_id)) {
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

        if (!$user->isAdmin()) {
            return $user->allowedSubdivisionId();
        }

        $requested = (int) $request->query('subdivision_id', 0);
        if ($requested > 0 && Subdivision::whereKey($requested)->exists()) {
            return $requested;
        }

        return Subdivision::where('status', 'Active')
            ->orderBy('subdivision_name')
            ->value('subdivision_id');
    }

    private function resolveSubmittedSubdivisionId(Request $request): ?int
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
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
        if (!in_array($tab, ['check-in', 'check-out', 'history'], true)) {
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

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            return str_replace('T', ' ', $value) . ':00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
            return strlen($value) === 16 ? $value . ':00' : $value;
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
            ->where('status', 'Checked Out')
            ->orderByDesc('check_in');

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

        if (!$user->isAdmin()) {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        return $query;
    }

    private function visitorReportViewData(
        Request $request,
        bool $autoPrint = false,
        bool $includePhotos = true,
        bool $renderingPdf = false
    ): array {
        $visitors = $this->buildVisitorHistoryQuery($request)->get();
        $reportRows = $this->buildVisitorReportRows($visitors, $includePhotos);

        return [
            'title' => 'Visitor Report',
            'generatedAt' => now(),
            'reportRows' => $reportRows,
            'autoPrint' => $autoPrint,
            'renderingPdf' => $renderingPdf,
            'reportQuery' => $request->query(),
            'previewMode' => $request->boolean('preview'),
        ];
    }

    private function buildVisitorReportRows($visitors, bool $includePhotos): array
    {
        return $visitors->map(function (Visitor $visitor) use ($includePhotos) {
            $photoUrl = $visitor->id_photo_path
                ? route('visitors.photo', ['visitor' => $visitor->visitor_id])
                : null;
            $photoDataUri = null;

            if ($includePhotos && $visitor->id_photo_path) {
                $absolutePath = $this->visitorPhotoAbsolutePath($visitor->id_photo_path);
                if ($absolutePath && File::exists($absolutePath)) {
                    $mime = File::mimeType($absolutePath) ?: 'image/png';
                    $contents = File::get($absolutePath);
                    $photoDataUri = 'data:' . $mime . ';base64,' . base64_encode($contents);
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
                'status' => $visitor->status ?: '-',
                'photo_url' => $photoUrl,
                'photo_data_uri' => $photoDataUri,
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
        $file = $request->file('id_photo');
        if (!$file) {
            abort(422, 'ID photo is required.');
        }

        return $file->store('uploads/visitors/id-photos', 'public');
    }

    private function visitorPhotoAbsolutePath(?string $path): ?string
    {
        if (!$path || !str_starts_with($path, 'uploads/visitors/id-photos/')) {
            return null;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        $legacyPath = public_path($path);

        return File::exists($legacyPath) ? $legacyPath : null;
    }
}
