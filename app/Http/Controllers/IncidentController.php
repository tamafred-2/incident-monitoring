<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Incident;
use App\Models\IncidentPhoto;
use App\Models\Subdivision;
use App\Models\User;
use App\Notifications\IncidentUpdatedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IncidentController extends Controller
{
    private ?bool $statusSchemaIsLegacy = null;

    public function index(Request $request): View
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);
        $filterPeriod = $this->resolvePeriodFilter((string) $request->query('period', ''));
        $filterType = trim((string) $request->query('type', ''));
        $filterDateFrom = $this->normalizeDateInput($request->query('date_from'));
        $filterDateTo = $this->normalizeDateInput($request->query('date_to'));
        $filterTimeFrom = $this->normalizeTimeInput($request->query('time_from'));
        $filterTimeTo = $this->normalizeTimeInput($request->query('time_to'));
        $historyView = $this->resolveHistoryView($request->query('view'));
        $reportedSort = strtolower((string) $request->query('reported_sort', 'desc'));
        if (!in_array($reportedSort, ['asc', 'desc'], true)) {
            $reportedSort = 'desc';
        }
        $perPage = $this->resolvePerPageChoice(
            $request->query('per_page_custom'),
            $request->query('per_page'),
            10
        );

        $query = Incident::query()
            ->with(['subdivision', 'house', 'proofPhotos', 'reporter'])
            ->orderByRaw('COALESCE(reported_at, incident_date, created_at) ' . strtoupper($reportedSort));

        if ($filterQ !== '') {
            $query->where(function (Builder $builder) use ($filterQ) {
                $builder->where('report_id', 'like', "%{$filterQ}%")
                    ->orWhere('description', 'like', "%{$filterQ}%")
                    ->orWhere('category', 'like', "%{$filterQ}%")
                    ->orWhere('location', 'like', "%{$filterQ}%")
                    ->orWhere('status', 'like', "%{$filterQ}%")
                    ->orWhereHas('reporter', function (Builder $reporterQuery) use ($filterQ) {
                        $reporterQuery->where('full_name', 'like', "%{$filterQ}%")
                            ->orWhere('email', 'like', "%{$filterQ}%");
                    });
            });
        }

        $this->applyIncidentTypeFilter($query, $filterType);
        $this->applyDateTimeFilters(
            $query,
            'COALESCE(reported_at, incident_date, created_at)',
            $filterPeriod,
            $filterDateFrom,
            $filterDateTo,
            $filterTimeFrom,
            $filterTimeTo
        );

        $this->applyHistoryViewScope($query, $historyView);

        if ($user->isResident()) {
            $query->where('reported_by', $user->user_id);
        } elseif (!$user->isAdmin()) {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        $incidents = $query
            ->paginate($perPage)
            ->withQueryString();
        $subdivisions = $user->isAdmin()
            ? Subdivision::orderBy('subdivision_name')->get()
            : collect();
        $reportSubdivisions = $user->isAdmin()
            ? Subdivision::where('status', 'Active')->orderBy('subdivision_name')->get()
            : collect();
        $effectiveSubdivision = $this->resolveEffectiveSubdivisionId($request);
        $openReportModal = $request->boolean('report');
        $incidentCategories = $this->incidentCategories();
        $residentReporter = null;
        $houses = $this->housesForUser($user, $effectiveSubdivision);

        return view('incidents.index', compact(
            'incidents',
            'subdivisions',
            'filterQ',
            'filterSubdivision',
            'reportSubdivisions',
            'effectiveSubdivision',
            'openReportModal',
            'historyView',
            'reportedSort',
            'incidentCategories',
            'residentReporter',
            'houses',
            'perPage',
            'filterPeriod',
            'filterType',
            'filterDateFrom',
            'filterDateTo',
            'filterTimeFrom',
            'filterTimeTo',
        ));
    }

    public function show(Request $request, int $incidentId): View
    {
        $incident = $this->findIncidentOrFail($request, $incidentId);
        $incident->load(['subdivision', 'house', 'proofPhotos', 'reporter']);

        return view('incidents.show', [
            'incident' => $incident,
            'indexContext' => $this->indexContext($request),
            'proofPhotos' => $this->proofPhotosFor($incident),
            'canEditIncident' => $this->canUserEditIncident($request->user(), $incident),
        ]);
    }

    public function export(Request $request): Response|StreamedResponse|RedirectResponse
    {
        $format = strtolower((string) $request->query('format', 'excel'));
        if (!in_array($format, ['excel', 'pdf'], true)) {
            return back()->with('error', 'Unsupported export format.');
        }

        if ($format === 'pdf') {
            $data = $this->reportViewData($request, false, true, true, true);

            try {
                $pdf = Pdf::loadView('incidents.report-print', $data)->setPaper('a4', 'landscape');

                return $pdf->download('incident-report-' . now()->format('Ymd_His') . '.pdf');
            } catch (\Throwable $exception) {
                report($exception);

                $message = extension_loaded('gd')
                    ? 'PDF export failed on this server. Please try again. If it continues, check server logs.'
                    : 'PDF export failed on this server. Enable PHP GD extension for image-capable PDF export.';

                return back()->with(
                    'error',
                    $message
                );
            }
        }

        $incidents = $this->buildIncidentReportQuery($request, true)->get();
        $reportRows = $this->buildIncidentReportRows($incidents, false);
        $filename = 'incident-report-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($reportRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Report ID', 'Category', 'Location', 'Status', 'Reporter', 'Date Reported', 'Date Resolved', 'Proof Image URL']);

            foreach ($reportRows as $row) {
                fputcsv($handle, [
                    $row['report_id'],
                    $row['category'],
                    $row['location'],
                    $row['status'],
                    $row['reporter'],
                    $row['reported_at'],
                    $row['resolved_at'],
                    $row['proof_url'] ?: '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request): View
    {
        return view('incidents.report-print', $this->reportViewData(
            $request,
            $request->boolean('autoprint'),
            true
        ));
    }

    public function showByReportId(Request $request, string $reportId): View
    {
        $incident = Incident::query()
            ->with(['subdivision', 'house', 'proofPhotos', 'reporter'])
            ->where('report_id', strtoupper(trim($reportId)))
            ->firstOrFail();

        $this->authorizeIncidentAccess($request, $incident);

        return view('incidents.show', [
            'incident' => $incident,
            'indexContext' => [],
            'proofPhotos' => $this->proofPhotosFor($incident),
            'canEditIncident' => $this->canUserEditIncident($request->user(), $incident),
        ]);
    }

    public function qrCard(Request $request, int $incidentId): View|Response
    {
        $incident = $this->findIncidentOrFail($request, $incidentId);
        $incident->load(['subdivision', 'house', 'reporter']);

        return view('incidents.qr-card', [
            'incident' => $incident,
            'qrPayload' => 'INCIDENT:' . $incident->report_id,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $effectiveSubdivision = $this->resolveEffectiveSubdivisionId($request);
        $context = $this->routeContext($request, $effectiveSubdivision);
        $context['report'] = 1;

        return redirect()->route('incidents.index', $context);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            $this->incidentValidationRules(
                false
            )
        );

        $subdivisionId = $this->resolveSubmittedSubdivisionId($request);
        if (!$subdivisionId) {
            return back()->withErrors(['subdivision_id' => 'Please select a valid subdivision.'])->withInput();
        }

        $houseId = $this->resolveHouseId($data, $subdivisionId);
        if (!$houseId) {
            return back()->withErrors([
                'house_id' => 'Please select a valid house for the selected subdivision.',
            ])->withInput();
        }

        $status = $this->mapIncidentStatusForStorage((string) $data['status']);
        $proofPhotoUploadCount = count((array) $request->file('proof_photos', []));
        if ($this->requiresProofPhotoForStatus($status) && $proofPhotoUploadCount < 1) {
            return back()->withErrors([
                'proof_photos' => 'At least one proof image is required when incident status is Resolved (Close).',
            ])->withInput();
        }
        $proofPhotoPaths = $this->storeProofPhotos($request);

        $incident = Incident::create([
            'subdivision_id' => $subdivisionId,
            'house_id' => $houseId,
            'description' => $data['description'] ?: null,
            'category' => $this->resolveCategory($data),
            'location' => $this->resolveLocation($data),
            'incident_date' => $data['incident_date'],
            'reported_at' => $data['reported_at'],
            'resolved_at' => $this->resolveResolvedAt(['status' => $status, 'resolved_at' => $data['resolved_at'] ?? null, 'reported_at' => $data['reported_at']], null),
            'status' => $status,
            'proof_photo_path' => $proofPhotoPaths[0] ?? null,
            'reported_by' => $request->user()->user_id,
            'assigned_to' => null,
        ]);

        $this->syncIncidentPhotoRecords($incident, $proofPhotoPaths, false);
        $this->notifyIncidentTeamNewReport($incident);

        return redirect()->route('incidents.index', $this->routeContext($request, $subdivisionId))
            ->with('success', 'Incident reported successfully.');
    }

    public function edit(Request $request, int $incidentId): View
    {
        $incident = $this->findIncidentOrFail($request, $incidentId);
        $incident->load(['subdivision', 'house', 'proofPhotos', 'reporter']);
        $this->authorizeIncidentEdit($request, $incident);

        return view('incidents.edit', [
            'incident' => $incident,
            'houses' => House::where('subdivision_id', $incident->subdivision_id)->orderBy('block')->orderBy('lot')->get(),
            'indexContext' => $this->indexContext($request),
            'proofPhotos' => $this->proofPhotosFor($incident),
            'incidentCategories' => $this->incidentCategories(),
            'isFullEditor' => $request->user()->isAdmin() || $request->user()->hasRole(['staff']),
        ]);
    }

    public function update(Request $request, int $incidentId): RedirectResponse
    {
        $incident = $this->findIncidentOrFail($request, $incidentId);
        $this->authorizeIncidentEdit($request, $incident);
        $isFullEditor = $request->user()->isAdmin() || $request->user()->hasRole(['staff']);
        $previousStatus = $incident->status;
        $data = $request->validate($isFullEditor
            ? $this->incidentValidationRules(false)
            : $this->incidentStatusValidationRules()
        );
        $status = $this->mapIncidentStatusForStorage((string) $data['status']);
        $existingProofPhotoPaths = $this->proofPhotosFor($incident->load('proofPhotos'))
            ->pluck('path')
            ->all();
        $proofPhotosToRemove = $this->resolveRemovableProofPhotoPaths(
            $existingProofPhotoPaths,
            $data['remove_proof_photos'] ?? []
        );
        $newProofPhotoUploadCount = count((array) $request->file('proof_photos', []));
        $remainingProofPhotoCount = count(array_diff($existingProofPhotoPaths, $proofPhotosToRemove));

        if ($this->requiresProofPhotoForStatus($status) && ($remainingProofPhotoCount + $newProofPhotoUploadCount) < 1) {
            return back()->withErrors([
                'proof_photos' => 'At least one proof image is required when incident status is Resolved (Close).',
            ])->withInput();
        }

        if ($isFullEditor) {
            $subdivisionId = $this->resolveSubmittedSubdivisionId($request);
            if (!$subdivisionId) {
                return back()->withErrors(['subdivision_id' => 'Please select a valid subdivision.'])->withInput();
            }

            $houseId = $this->resolveHouseId($data, $subdivisionId);
            if (!$houseId) {
                return back()->withErrors([
                    'house_id' => 'Please select a valid house for the selected subdivision.',
                ])->withInput();
            }
            $incident->update([
                'subdivision_id' => $subdivisionId,
                'house_id' => $houseId,
                'description' => $data['description'] ?: null,
                'category' => $this->resolveCategory($data),
                'location' => $this->resolveLocation($data),
                'incident_date' => $data['incident_date'],
                'reported_at' => $data['reported_at'],
                'resolved_at' => $this->resolveResolvedAt(['status' => $status, 'resolved_at' => $data['resolved_at'] ?? null, 'reported_at' => $data['reported_at']], $incident),
                'status' => $status,
                'assigned_to' => null,
            ]);

            $this->notifyIncidentStatusIfNeeded($incident, $previousStatus);
        } else {
            $incident->update([
                'resolved_at' => $this->resolveResolvedAt([
                    'status' => $status,
                    'resolved_at' => $data['resolved_at'] ?? null,
                    'reported_at' => optional($incident->reported_at)->format('Y-m-d H:i:s'),
                ], $incident),
                'status' => $status,
            ]);
            $subdivisionId = $incident->subdivision_id;

            $this->notifyIncidentStatusIfNeeded($incident, $previousStatus);
        }

        foreach ($proofPhotosToRemove as $photoPath) {
            $this->deleteProofPhoto($photoPath);
        }

        $remainingProofPhotoPaths = array_values(array_diff($existingProofPhotoPaths, $proofPhotosToRemove));
        $newProofPhotoPaths = $this->storeProofPhotos($request);
        $finalProofPhotoPaths = array_values(array_unique(array_merge($remainingProofPhotoPaths, $newProofPhotoPaths)));

        $this->syncIncidentPhotoRecords($incident, $finalProofPhotoPaths, false);

        return redirect()->route('incidents.show', array_merge(
            ['incidentId' => $incident->incident_id],
            $this->indexContext($request)
        ))->with('success', 'Incident updated successfully.');
    }

    public function verifyOnScene(Request $request, int $incidentId): RedirectResponse
    {
        $incident = $this->findIncidentOrFail($request, $incidentId);
        $user = $request->user();

        if (!$user->isAdmin() && !$user->canAccessSubdivision($incident->subdivision_id)) {
            abort(403);
        }

        if ($incident->verified_on_site_at) {
            return back()->with('success', 'This incident has already been verified on site.');
        }

        $status = $this->isPrimaryPendingStatus($incident->status)
            ? $this->secondaryPendingStatus()
            : $incident->status;
        $incident->update([
            'verified_by_staff_id' => $user->user_id,
            'verified_on_site_at' => now(),
            'status' => $status,
        ]);

        $this->notifyIncidentVerification($incident);

        return back()->with('success', 'Incident verified on site successfully.');
    }

    public function destroy(Request $request, int $incidentId): RedirectResponse
    {
        $incident = $this->findIncidentOrFail($request, $incidentId);
        $incident->delete();

        return redirect()->route('incidents.index', $this->indexContext($request))
            ->with('success', 'Incident archived successfully.');
    }

    public function restore(Request $request, int $incidentId): RedirectResponse
    {
        $incident = $this->findIncidentOrFail($request, $incidentId, true);

        if (!$incident->trashed()) {
            return redirect()->route('incidents.index', $this->indexContext($request))
                ->with('error', 'That incident is already active.');
        }

        $incident->restore();

        return redirect()->route('incidents.index', $this->indexContext($request))
            ->with('success', 'Incident restored successfully.');
    }

    public function forceDelete(Request $request, int $incidentId): RedirectResponse
    {
        $incident = $this->findIncidentOrFail($request, $incidentId, true);

        if (!$incident->trashed()) {
            return redirect()->route('incidents.index', $this->indexContext($request))
                ->with('error', 'Only archived incidents can be permanently deleted.');
        }

        $proofPhotos = $this->proofPhotosFor($incident->load('proofPhotos'));

        foreach ($proofPhotos as $photo) {
            $this->deleteProofPhoto($photo['path']);
        }

        IncidentPhoto::query()->where('incident_id', $incident->incident_id)->delete();
        $incident->forceDelete();

        return redirect()->route('incidents.index', $this->indexContext($request))
            ->with('success', 'Incident permanently deleted.');
    }

    public function housesBySubdivision(Request $request): JsonResponse
    {
        $subdivisionId = (int) $request->query('subdivision_id', 0);

        if (!$subdivisionId || !$request->user()->canAccessSubdivision($subdivisionId)) {
            return response()->json([]);
        }

        $houses = House::where('subdivision_id', $subdivisionId)
            ->orderBy('block')
            ->orderBy('lot')
            ->get(['house_id', 'block', 'lot']);

        return response()->json($houses->map(fn ($h) => [
            'house_id' => $h->house_id,
            'display_address' => $h->display_address,
        ]));
    }

    public function photo(Request $request, string $path): BinaryFileResponse
    {
        abort_unless(str_starts_with($path, 'uploads/incidents/'), 404);

        $incident = Incident::query()
            ->whereHas('proofPhotos', function (Builder $builder) use ($path) {
                $builder->where('photo_path', $path);
            })
            ->orWhere('proof_photo_path', $path)
            ->first();

        abort_unless($incident !== null, 404);
        $this->authorizeIncidentAccess($request, $incident);

        $absolutePath = $this->proofPhotoAbsolutePath($path);
        abort_unless($absolutePath !== null, 404);

        return response()->file($absolutePath);
    }

    private function incidentValidationRules(bool $forResident = false): array
    {
        $rules = [
            'subdivision_id' => ['nullable', 'integer'],
            'house_id' => ['required', 'integer', 'exists:houses,house_id'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100', Rule::in($this->incidentCategories())],
            'category_other' => ['nullable', 'string', 'max:100', 'required_if:category,Other'],
            'location' => ['required', 'string', 'max:150'],
            'location_other' => ['nullable', 'string', 'max:150', 'required_if:location,__other__'],
            'incident_date' => ['required', 'date'],
            'proof_photos' => ['nullable', 'array', 'max:10'],
            'proof_photos.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_proof_photos' => ['nullable', 'array'],
            'remove_proof_photos.*' => ['string'],
        ];

        if ($forResident) {
            return $rules;
        }

        $rules['reported_at'] = ['required', 'date'];
        $rules['resolved_at'] = ['nullable', 'date', 'after_or_equal:reported_at'];
        $rules['status'] = ['required', Rule::in($this->allowedIncidentStatusesForInput())];

        return $rules;
    }

    private function incidentStatusValidationRules(): array
    {
        return [
            'status' => ['required', Rule::in($this->allowedIncidentStatusesForInput())],
            'resolved_at' => ['nullable', 'date'],
            'proof_photos' => ['nullable', 'array', 'max:10'],
            'proof_photos.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_proof_photos' => ['nullable', 'array'],
            'remove_proof_photos.*' => ['string'],
        ];
    }

    private function resolveHouseId(array $data, int $subdivisionId): ?int
    {
        $houseId = (int) ($data['house_id'] ?? 0);
        if ($houseId < 1) {
            return null;
        }

        return House::where('house_id', $houseId)->where('subdivision_id', $subdivisionId)->exists()
            ? $houseId
            : null;
    }

    private function resolveLocation(array $data): ?string
    {
        if (($data['location'] ?? null) === '__other__') {
            return trim((string) ($data['location_other'] ?? '')) ?: null;
        }

        return trim((string) ($data['location'] ?? '')) ?: null;
    }

    private function housesForUser($user, ?int $subdivisionId): \Illuminate\Support\Collection
    {
        if (!$subdivisionId) {
            return collect();
        }

        return House::where('subdivision_id', $subdivisionId)
            ->orderBy('block')
            ->orderBy('lot')
            ->get();
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

    private function routeContext(Request $request, int|string|null $subdivisionId): array
    {
        if (!$request->user()->isAdmin()) {
            return [];
        }

        $context = $subdivisionId ? ['subdivision_id' => (int) $subdivisionId] : [];
        $reportedSort = strtolower((string) $request->input('reported_sort', $request->query('reported_sort', 'desc')));
        if (in_array($reportedSort, ['asc', 'desc'], true)) {
            $context['reported_sort'] = $reportedSort;
        }
        $context['per_page'] = $this->resolvePerPageChoice(
            $request->input('per_page_custom', $request->query('per_page_custom')),
            $request->input('per_page', $request->query('per_page')),
            10
        );
        $this->appendSharedFilterContext($context, $request);

        return $context;
    }

    private function indexContext(Request $request): array
    {
        $context = [];

        if ($request->user()->isAdmin()) {
            $filterQ = trim((string) $request->input('q', $request->query('q', '')));
            if ($filterQ !== '') {
                $context['q'] = $filterQ;
            }

            $subdivisionId = (int) $request->input('subdivision_id', $request->query('subdivision_id', 0));
            if ($subdivisionId > 0) {
                $context['subdivision_id'] = $subdivisionId;
            }

            $view = $this->resolveHistoryView($request->input('view', $request->query('view')));
            if ($view !== 'active') {
                $context['view'] = $view;
            }

            $reportedSort = strtolower((string) $request->input('reported_sort', $request->query('reported_sort', 'desc')));
            if ($reportedSort === 'asc') {
                $context['reported_sort'] = 'asc';
            }
        }

        $context['per_page'] = $this->resolvePerPage($request->input('per_page', $request->query('per_page')), 10);
        $this->appendSharedFilterContext($context, $request);

        return $context;
    }

    private function appendSharedFilterContext(array &$context, Request $request): void
    {
        $period = $this->resolvePeriodFilter((string) $request->input('period', $request->query('period', '')));
        if ($period !== '') {
            $context['period'] = $period;
        }

        $type = trim((string) $request->input('type', $request->query('type', '')));
        if ($type !== '') {
            $context['type'] = $type;
        }

        $dateFrom = $this->normalizeDateInput($request->input('date_from', $request->query('date_from')));
        $dateTo = $this->normalizeDateInput($request->input('date_to', $request->query('date_to')));
        $timeFrom = $this->normalizeTimeInput($request->input('time_from', $request->query('time_from')));
        $timeTo = $this->normalizeTimeInput($request->input('time_to', $request->query('time_to')));

        if ($dateFrom !== null) {
            $context['date_from'] = $dateFrom;
        }
        if ($dateTo !== null) {
            $context['date_to'] = $dateTo;
        }
        if ($timeFrom !== null) {
            $context['time_from'] = $timeFrom;
        }
        if ($timeTo !== null) {
            $context['time_to'] = $timeTo;
        }
    }

    private function applyIncidentTypeFilter(Builder $query, string $type): void
    {
        if ($type === '') {
            return;
        }

        $query->where('category', $type);
    }

    private function resolvePeriodFilter(string $period): string
    {
        $period = strtolower(trim($period));

        return in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : '';
    }

    private function normalizeDateInput(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function normalizeTimeInput(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{2}:\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function applyDateTimeFilters(
        Builder $query,
        string $dateTimeExpression,
        string $period,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $timeFrom,
        ?string $timeTo
    ): void {
        if ($period !== '') {
            $today = now();
            if ($period === 'daily') {
                $dateFrom = $today->toDateString();
                $dateTo = $today->toDateString();
            } elseif ($period === 'weekly') {
                $dateFrom = $today->copy()->startOfWeek()->toDateString();
                $dateTo = $today->copy()->endOfWeek()->toDateString();
            } elseif ($period === 'monthly') {
                $dateFrom = $today->copy()->startOfMonth()->toDateString();
                $dateTo = $today->copy()->endOfMonth()->toDateString();
            }
        }

        if ($dateFrom !== null) {
            $query->whereRaw("date({$dateTimeExpression}) >= ?", [$dateFrom]);
        }
        if ($dateTo !== null) {
            $query->whereRaw("date({$dateTimeExpression}) <= ?", [$dateTo]);
        }
        if ($timeFrom !== null) {
            $query->whereRaw("time({$dateTimeExpression}) >= ?", [$timeFrom . ':00']);
        }
        if ($timeTo !== null) {
            $query->whereRaw("time({$dateTimeExpression}) <= ?", [$timeTo . ':59']);
        }
    }

    private function storeProofPhotos(Request $request): array
    {
        $files = $request->file('proof_photos', []);
        if (!is_array($files) || $files === []) {
            return [];
        }

        $paths = [];

        foreach ($files as $file) {
            $filename = 'incident_' . now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $file->getClientOriginalExtension();
            $storedPath = $file->storeAs('uploads/incidents', $filename, 'public');

            if (!is_string($storedPath)) {
                throw ValidationException::withMessages([
                    'proof_photos' => 'Unable to save uploaded proof photos. Please try again.',
                ]);
            }

            $paths[] = $storedPath;
        }

        return $paths;
    }

    private function syncIncidentPhotoRecords(Incident $incident, array $newPhotoPaths, bool $append): void
    {
        $existingPaths = $append ? $this->proofPhotosFor($incident->fresh('proofPhotos'))->pluck('path')->all() : [];
        $allPaths = array_values(array_unique(array_merge($existingPaths, $newPhotoPaths)));

        IncidentPhoto::query()->where('incident_id', $incident->incident_id)->delete();

        foreach ($allPaths as $index => $photoPath) {
            IncidentPhoto::create([
                'incident_id' => $incident->incident_id,
                'photo_path' => $photoPath,
                'sort_order' => $index,
            ]);
        }

        $incident->forceFill([
            'proof_photo_path' => $allPaths[0] ?? null,
        ])->save();
    }

    private function resolveRemovableProofPhotoPaths(array $existingPaths, mixed $requestedPaths): array
    {
        if (!is_array($requestedPaths) || $requestedPaths === []) {
            return [];
        }

        $allowedExistingPaths = array_flip($existingPaths);

        return collect($requestedPaths)
            ->filter(fn (mixed $path) => is_string($path) && str_starts_with($path, 'uploads/incidents/'))
            ->filter(fn (string $path) => isset($allowedExistingPaths[$path]))
            ->unique()
            ->values()
            ->all();
    }

    private function resolveHistoryView(?string $view): string
    {
        return in_array($view, ['active', 'history'], true) ? $view : 'active';
    }

    private function applyHistoryViewScope(Builder $query, string $historyView): void
    {
        $query->whereNull('deleted_at');

        if ($historyView === 'history') {
            $query->whereIn('status', $this->resolvedStatuses());

            return;
        }

        $query->whereNotIn('status', $this->resolvedStatuses());
    }

    private function findIncidentOrFail(Request $request, int $incidentId, bool $withTrashed = false): Incident
    {
        $query = Incident::query();
        if ($withTrashed) {
            $query->withTrashed();
        }

        $incident = $query->findOrFail($incidentId);

        $this->authorizeIncidentAccess($request, $incident);

        return $incident;
    }

    private function authorizeIncidentAccess(Request $request, Incident $incident): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isResident()) {
            abort_unless((int) $incident->reported_by === (int) $user->user_id, 403);

            return;
        }

        abort_unless($user->canAccessSubdivision($incident->subdivision_id), 403);
    }

    private function authorizeIncidentEdit(Request $request, Incident $incident): void
    {
        $user = $request->user();

        abort_unless($this->canUserEditIncident($user, $incident), 403);
    }

    private function canUserEditIncident(User $user, Incident $incident): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->hasRole(['staff']) || !$user->canAccessSubdivision($incident->subdivision_id)) {
            return false;
        }

        return !$this->isIncidentLockedForStaffEdit($incident);
    }

    private function isIncidentLockedForStaffEdit(Incident $incident): bool
    {
        if ($incident->isResolvedOrDone()) {
            return true;
        }

        return in_array($incident->status, $this->resolvedStatuses(), true);
    }

    private function proofPhotosFor(Incident $incident): Collection
    {
        $paths = collect();

        if ($incident->relationLoaded('proofPhotos')) {
            $paths = $paths->merge(
                $incident->proofPhotos->pluck('photo_path')
            );
        }

        if ($incident->proof_photo_path) {
            $paths->push($incident->proof_photo_path);
        }

        return $paths
            ->filter()
            ->unique()
            ->values()
            ->map(fn (string $path) => ['path' => $path, 'url' => route('incidents.photos.show', ['path' => $path])]);
    }

    private function proofPhotoAbsolutePath(string $path): ?string
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        $legacyPath = public_path($path);

        return File::exists($legacyPath) ? $legacyPath : null;
    }

    private function deleteProofPhoto(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $legacyPath = public_path($path);
        if (File::exists($legacyPath)) {
            File::delete($legacyPath);
        }
    }

    private function notifyIncidentTeamNewReport(Incident $incident): void
    {
        $team = User::query()
            ->where('subdivision_id', $incident->subdivision_id)
            ->whereIn('role', ['security', 'staff'])
            ->where('is_active', true)
            ->get();

        if ($team->isEmpty()) {
            return;
        }

        Notification::send($team, new IncidentUpdatedNotification(
            $incident,
            'New Incident Reported',
            "New incident {$incident->report_id} requires coordination."
        ));
    }

    private function notifyIncidentStatusIfNeeded(Incident $incident, string $previousStatus): void
    {
        if ($incident->status === $previousStatus) {
            return;
        }

        $reporter = $incident->reporter;
        if ($reporter) {
            Notification::send($reporter, new IncidentUpdatedNotification(
                $incident,
                'Incident Status Updated',
                "Your incident {$incident->report_id} is now {$incident->status}."
            ));
        }
    }

    private function notifyIncidentVerification(Incident $incident): void
    {
        $reporter = $incident->reporter;
        if ($reporter) {
            Notification::send($reporter, new IncidentUpdatedNotification(
                $incident,
                'Incident Verified On Site',
                "Your incident {$incident->report_id} has been verified on site by {$incident->verifiedStaff?->full_name}."
            ));
        }

        $verificationUser = $incident->verifiedStaff;
        if ($verificationUser && (! $reporter || $verificationUser->user_id !== $reporter->user_id)) {
            Notification::send($verificationUser, new IncidentUpdatedNotification(
                $incident,
                'Incident Verification Confirmed',
                "You have verified incident {$incident->report_id} on site."
            ));
        }
    }

    private function incidentCategories(): array
    {
        return [
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
    }

    private function resolveCategory(array $data): ?string
    {
        if (($data['category'] ?? null) === 'Other') {
            $customCategory = trim((string) ($data['category_other'] ?? ''));

            return $customCategory !== '' ? $customCategory : 'Other';
        }

        return !empty($data['category']) ? $data['category'] : null;
    }

    private function resolveResolvedAt(array $data, ?Incident $incident): ?string
    {
        if (!in_array($data['status'] ?? null, $this->resolvedStatuses(), true)) {
            return null;
        }

        if (!empty($data['resolved_at'])) {
            return $data['resolved_at'];
        }

        if ($incident?->resolved_at) {
            return $incident->resolved_at->format('Y-m-d H:i:s');
        }

        return $data['reported_at'] ?? null;
    }

    private function allowedIncidentStatusesForInput(): array
    {
        return [
            'Open',
            'Under Investigation',
            'Resolved',
        ];
    }

    private function mapIncidentStatusForStorage(string $status): string
    {
        if ($this->usesLegacyIncidentStatusSchema()) {
            return match ($status) {
                'Open' => 'Reported',
                'Under Investigation' => 'Investigating',
                default => $status,
            };
        }

        return match ($status) {
            'Reported' => 'Open',
            'Investigating', 'Ongoing' => 'Under Investigation',
            'Closed' => 'Resolved',
            default => $status,
        };
    }

    private function usesLegacyIncidentStatusSchema(): bool
    {
        if ($this->statusSchemaIsLegacy !== null) {
            return $this->statusSchemaIsLegacy;
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            return $this->statusSchemaIsLegacy = false;
        }

        $tableSql = DB::table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'incidents')
            ->value('sql');

        return $this->statusSchemaIsLegacy = is_string($tableSql)
            && str_contains($tableSql, "'Reported'")
            && str_contains($tableSql, "'Investigating'");
    }

    private function primaryPendingStatus(): string
    {
        return $this->usesLegacyIncidentStatusSchema() ? 'Reported' : 'Open';
    }

    private function secondaryPendingStatus(): string
    {
        return $this->usesLegacyIncidentStatusSchema() ? 'Investigating' : 'Under Investigation';
    }

    private function isPrimaryPendingStatus(?string $status): bool
    {
        return $status === $this->primaryPendingStatus();
    }

    private function resolvedStatuses(): array
    {
        return ['Resolved', 'Closed'];
    }

    private function requiresProofPhotoForStatus(?string $status): bool
    {
        return in_array($status, $this->resolvedStatuses(), true);
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

    private function buildIncidentReportQuery(Request $request, bool $historyOnly = false): Builder
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);
        $filterPeriod = $this->resolvePeriodFilter((string) $request->query('period', ''));
        $filterType = trim((string) $request->query('type', ''));
        $filterDateFrom = $this->normalizeDateInput($request->query('date_from'));
        $filterDateTo = $this->normalizeDateInput($request->query('date_to'));
        $filterTimeFrom = $this->normalizeTimeInput($request->query('time_from'));
        $filterTimeTo = $this->normalizeTimeInput($request->query('time_to'));
        $historyView = $historyOnly ? 'history' : $this->resolveHistoryView($request->query('view'));
        $reportedSort = strtolower((string) $request->query('reported_sort', 'desc'));
        if (!in_array($reportedSort, ['asc', 'desc'], true)) {
            $reportedSort = 'desc';
        }

        $query = Incident::query()
            ->with(['subdivision', 'house', 'reporter', 'proofPhotos'])
            ->orderByRaw('COALESCE(reported_at, incident_date, created_at) ' . strtoupper($reportedSort));

        if ($filterQ !== '') {
            $query->where(function (Builder $builder) use ($filterQ) {
                $builder->where('report_id', 'like', "%{$filterQ}%")
                    ->orWhere('description', 'like', "%{$filterQ}%")
                    ->orWhere('category', 'like', "%{$filterQ}%")
                    ->orWhere('location', 'like', "%{$filterQ}%")
                    ->orWhere('status', 'like', "%{$filterQ}%")
                    ->orWhereHas('reporter', function (Builder $reporterQuery) use ($filterQ) {
                        $reporterQuery->where('full_name', 'like', "%{$filterQ}%")
                            ->orWhere('email', 'like', "%{$filterQ}%");
                    });
            });
        }

        $this->applyIncidentTypeFilter($query, $filterType);
        $this->applyDateTimeFilters(
            $query,
            'COALESCE(reported_at, incident_date, created_at)',
            $filterPeriod,
            $filterDateFrom,
            $filterDateTo,
            $filterTimeFrom,
            $filterTimeTo
        );
        $this->applyHistoryViewScope($query, $historyView);

        if ($user->isResident()) {
            $query->where('reported_by', $user->user_id);
        } elseif (!$user->isAdmin()) {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        return $query;
    }

    private function reportViewData(
        Request $request,
        bool $autoPrint,
        bool $historyOnly = false,
        bool $includeProofImages = true,
        bool $renderingPdf = false
    ): array
    {
        $incidents = $this->buildIncidentReportQuery($request, $historyOnly)->get();
        $reportRows = $this->buildIncidentReportRows($incidents, $includeProofImages);
        $reportQuery = array_filter([
            'q' => trim((string) $request->query('q', '')) ?: null,
            'subdivision_id' => (int) $request->query('subdivision_id', 0) ?: null,
            'view' => 'history',
            'reported_sort' => in_array(strtolower((string) $request->query('reported_sort', 'desc')), ['asc', 'desc'], true)
                ? strtolower((string) $request->query('reported_sort', 'desc'))
                : null,
            'period' => $this->resolvePeriodFilter((string) $request->query('period', '')) ?: null,
            'type' => trim((string) $request->query('type', '')) ?: null,
            'date_from' => $this->normalizeDateInput($request->query('date_from')),
            'date_to' => $this->normalizeDateInput($request->query('date_to')),
            'time_from' => $this->normalizeTimeInput($request->query('time_from')),
            'time_to' => $this->normalizeTimeInput($request->query('time_to')),
        ]);

        return [
            'incidents' => $incidents,
            'reportRows' => $reportRows,
            'generatedAt' => now(),
            'autoPrint' => $autoPrint,
            'title' => 'Incident Report',
            'previewMode' => $request->boolean('preview'),
            'reportQuery' => $reportQuery,
            'includeProofImages' => $includeProofImages,
            'renderingPdf' => $renderingPdf,
        ];
    }

    private function buildIncidentReportRows(Collection $incidents, bool $includeProofImages): array
    {
        return $incidents->map(function (Incident $incident) use ($includeProofImages) {
            $proofPath = $incident->proofPhotos->first()->photo_path ?? $incident->proof_photo_path;
            $proofUrl = $proofPath ? route('incidents.photos.show', ['path' => $proofPath]) : null;
            $proofDataUri = null;

            if ($includeProofImages && $proofPath) {
                $absolutePath = $this->proofPhotoAbsolutePath($proofPath);
                if ($absolutePath && File::exists($absolutePath)) {
                    $mime = File::mimeType($absolutePath) ?: 'image/jpeg';
                    $proofDataUri = 'data:' . $mime . ';base64,' . base64_encode((string) File::get($absolutePath));
                }
            }

            return [
                'report_id' => $incident->report_id,
                'category' => $incident->category ?: '-',
                'location' => $incident->location ?: '-',
                'status' => $incident->status,
                'reporter' => $incident->reporter?->full_name ?: '-',
                'reported_at' => $incident->reported_at?->format('Y-m-d H:i:s') ?: '-',
                'resolved_at' => $incident->resolved_at?->format('Y-m-d H:i:s') ?: '-',
                'proof_url' => $proofUrl,
                'proof_data_uri' => $proofDataUri,
            ];
        })->values()->all();
    }
}
