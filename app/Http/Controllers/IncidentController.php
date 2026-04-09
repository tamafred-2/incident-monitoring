<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentPhoto;
use App\Models\Resident;
use App\Models\Subdivision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);
        $historyView = $this->resolveHistoryView($request->query('view'));

        $query = Incident::query()
            ->with(['subdivision', 'verifiedResident', 'proofPhotos', 'reporter'])
            ->orderByDesc('incident_date');

        if ($filterQ !== '') {
            $query->where(function (Builder $builder) use ($filterQ) {
                $builder->where('title', 'like', "%{$filterQ}%")
                    ->orWhere('description', 'like', "%{$filterQ}%")
                    ->orWhere('category', 'like', "%{$filterQ}%")
                    ->orWhere('location', 'like', "%{$filterQ}%")
                    ->orWhere('status', 'like', "%{$filterQ}%")
                    ->orWhereHas('verifiedResident', function (Builder $residentQuery) use ($filterQ) {
                        $residentQuery->where('full_name', 'like', "%{$filterQ}%")
                            ->orWhere('resident_code', 'like', "%{$filterQ}%");
                    })
                    ->orWhereHas('reporter', function (Builder $reporterQuery) use ($filterQ) {
                        $reporterQuery->where('full_name', 'like', "%{$filterQ}%")
                            ->orWhere('email', 'like', "%{$filterQ}%");
                    });
            });
        }

        if ($user->isAdmin()) {
            $this->applyHistoryViewScope($query, $historyView);
        }

        if ($user->isResident()) {
            $query->where('reported_by', $user->user_id);
        } elseif (!$user->isAdmin()) {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        $incidents = $query->get();
        $subdivisions = $user->isAdmin()
            ? Subdivision::orderBy('subdivision_name')->get()
            : collect();
        $reportSubdivisions = $user->isAdmin()
            ? Subdivision::where('status', 'Active')->orderBy('subdivision_name')->get()
            : collect();
        $effectiveSubdivision = $this->resolveEffectiveSubdivisionId($request);
        $openReportModal = $request->boolean('report');
        $incidentCategories = $this->incidentCategories();
        $residentReporter = $user->isResident() ? $user->loadMissing('resident.house') : null;

        return view('incidents.index', compact(
            'incidents',
            'subdivisions',
            'filterQ',
            'filterSubdivision',
            'reportSubdivisions',
            'effectiveSubdivision',
            'openReportModal',
            'historyView',
            'incidentCategories',
            'residentReporter',
        ));
    }

    public function show(Request $request, int $incidentId): View
    {
        $incident = $this->findIncidentOrFail($request, $incidentId, true);
        $incident->load(['subdivision', 'verifiedResident', 'proofPhotos', 'reporter']);

        return view('incidents.show', [
            'incident' => $incident,
            'indexContext' => $this->indexContext($request),
            'proofPhotos' => $this->proofPhotosFor($incident),
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
        $data = $request->validate($this->incidentValidationRules($request->user()->isResident()));

        $subdivisionId = $this->resolveSubmittedSubdivisionId($request);
        if (!$subdivisionId) {
            return back()->withErrors(['subdivision_id' => 'Please select a valid subdivision.'])->withInput();
        }

        [$verifiedResidentId, $verificationMethod, $verifiedAt] = $request->user()->isResident()
            ? $this->resolveResidentAccountVerificationData($request->user(), $subdivisionId)
            : $this->resolveVerificationData($data, $subdivisionId);
        $proofPhotoPaths = $this->storeProofPhotos($request);

        $incident = Incident::create([
            'subdivision_id' => $subdivisionId,
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'category' => $this->resolveCategory($data),
            'location' => $data['location'] ?: null,
            'incident_date' => $data['incident_date'],
            'reported_at' => $request->user()->isResident() ? now() : $data['reported_at'],
            'resolved_at' => $request->user()->isResident() ? null : $this->resolveResolvedAt($data, null),
            'status' => $request->user()->isResident() ? 'Open' : $data['status'],
            'proof_photo_path' => $proofPhotoPaths[0] ?? null,
            'reported_by' => $request->user()->user_id,
            'verified_resident_id' => $verifiedResidentId,
            'verification_method' => $verificationMethod,
            'verified_at' => $verifiedAt,
        ]);

        $this->syncIncidentPhotoRecords($incident, $proofPhotoPaths, false);

        return redirect()->route('incidents.index', $this->routeContext($request, $subdivisionId))
            ->with('success', 'Incident reported successfully.');
    }

    public function edit(Request $request, int $incidentId): View
    {
        $incident = $this->findIncidentOrFail($request, $incidentId);
        $incident->load(['subdivision', 'verifiedResident', 'proofPhotos', 'reporter']);

        return view('incidents.edit', [
            'incident' => $incident,
            'subdivisions' => Subdivision::where('status', 'Active')->orderBy('subdivision_name')->get(),
            'indexContext' => $this->indexContext($request),
            'proofPhotos' => $this->proofPhotosFor($incident),
            'incidentCategories' => $this->incidentCategories(),
        ]);
    }

    public function update(Request $request, int $incidentId): RedirectResponse
    {
        $incident = $this->findIncidentOrFail($request, $incidentId);
        $data = $request->validate($this->incidentValidationRules(false, false));

        $subdivisionId = $this->resolveSubmittedSubdivisionId($request);
        if (!$subdivisionId) {
            return back()->withErrors(['subdivision_id' => 'Please select a valid subdivision.'])->withInput();
        }

        $incident->update([
            'subdivision_id' => $subdivisionId,
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'category' => $this->resolveCategory($data),
            'location' => $data['location'] ?: null,
            'incident_date' => $data['incident_date'],
            'reported_at' => $data['reported_at'],
            'resolved_at' => $this->resolveResolvedAt($data, $incident),
            'status' => $data['status'],
        ]);

        $proofPhotoPaths = $this->storeProofPhotos($request);
        if ($proofPhotoPaths !== []) {
            $this->syncIncidentPhotoRecords($incident, $proofPhotoPaths, true);
        } else {
            $existingProofPhotos = $this->proofPhotosFor($incident->fresh('proofPhotos'));
            $incident->forceFill([
                'proof_photo_path' => $existingProofPhotos->first()['path'] ?? $incident->proof_photo_path,
            ])->save();
        }

        return redirect()->route('incidents.show', array_merge(
            ['incidentId' => $incident->incident_id],
            $this->indexContext($request)
        ))->with('success', 'Incident updated successfully.');
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
            $absolutePath = public_path($photo['path']);
            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        IncidentPhoto::query()->where('incident_id', $incident->incident_id)->delete();
        $incident->forceDelete();

        return redirect()->route('incidents.index', $this->indexContext($request))
            ->with('success', 'Incident permanently deleted.');
    }

    public function verifyResident(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'subdivision_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        if (!$user->canAccessSubdivision($data['subdivision_id'])) {
            return response()->json([
                'success' => false,
                'error' => 'Access denied to this subdivision.',
            ]);
        }

        $resident = Resident::query()
            ->with('house')
            ->where('resident_code', trim($data['code']))
            ->where('subdivision_id', (int) $data['subdivision_id'])
            ->first();

        if (!$resident) {
            return response()->json([
                'success' => false,
                'error' => 'Resident not found for this subdivision.',
            ]);
        }

        if ($resident->status !== 'Active') {
            return response()->json([
                'success' => false,
                'error' => 'Resident is not active.',
            ]);
        }

        return response()->json([
            'success' => true,
            'resident_id' => (int) $resident->resident_id,
            'full_name' => $resident->full_name,
            'address_or_unit' => $resident->display_address ?? '',
        ]);
    }

    private function incidentValidationRules(bool $forResident = false, bool $includeVerification = true): array
    {
        $rules = [
            'subdivision_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100', Rule::in($this->incidentCategories())],
            'category_other' => ['nullable', 'string', 'max:100', 'required_if:category,Other'],
            'location' => ['nullable', 'string', 'max:150'],
            'incident_date' => ['required', 'date'],
            'proof_photos' => ['nullable', 'array', 'max:10'],
            'proof_photos.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];

        if ($forResident) {
            return $rules;
        }

        $rules['reported_at'] = ['required', 'date'];
        $rules['resolved_at'] = ['nullable', 'date', 'after_or_equal:reported_at'];
        $rules['status'] = ['required', 'in:Open,Under Investigation,Resolved,Closed'];

        if ($includeVerification) {
            $rules['verified_resident_id'] = ['nullable', 'integer'];
            $rules['verification_method'] = ['nullable', 'in:qr_scan,manual_code'];
        }

        return $rules;
    }

    private function resolveVerificationData(array $data, int $subdivisionId): array
    {
        $verifiedResidentId = null;
        $verificationMethod = null;
        $verifiedAt = null;

        if (!empty($data['verified_resident_id']) && !empty($data['verification_method'])) {
            $resident = Resident::query()
                ->whereKey((int) $data['verified_resident_id'])
                ->where('subdivision_id', $subdivisionId)
                ->where('status', 'Active')
                ->first();

            if ($resident) {
                $verifiedResidentId = $resident->resident_id;
                $verificationMethod = $data['verification_method'];
                $verifiedAt = now();
            }
        }

        return [$verifiedResidentId, $verificationMethod, $verifiedAt];
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

        return $subdivisionId ? ['subdivision_id' => (int) $subdivisionId] : [];
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
        }

        return $context;
    }

    private function storeProofPhotos(Request $request): array
    {
        $files = $request->file('proof_photos', []);
        if (!is_array($files) || $files === []) {
            return [];
        }

        $directory = public_path('uploads/incidents');
        File::ensureDirectoryExists($directory);

        $paths = [];

        foreach ($files as $file) {
            $filename = 'incident_' . now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $filename);
            $paths[] = 'uploads/incidents/' . $filename;
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

    private function resolveHistoryView(?string $view): string
    {
        return in_array($view, ['active', 'deleted', 'all'], true) ? $view : 'active';
    }

    private function applyHistoryViewScope(Builder $query, string $historyView): void
    {
        if ($historyView === 'deleted') {
            $query->onlyTrashed();

            return;
        }

        if ($historyView === 'all') {
            $query->withTrashed();
        }
    }

    private function findIncidentOrFail(Request $request, int $incidentId, bool $withTrashed = false): Incident
    {
        $query = Incident::query();
        if ($withTrashed) {
            $query->withTrashed();
        }

        $incident = $query->findOrFail($incidentId);

        if ($request->user()->isResident()) {
            abort_unless((int) $incident->reported_by === (int) $request->user()->user_id, 403);
        } else {
            abort_unless($request->user()->canAccessSubdivision($incident->subdivision_id), 403);
        }

        return $incident;
    }

    private function resolveResidentAccountVerificationData($user, int $subdivisionId): array
    {
        $resident = $user->resident;

        if (!$resident || (int) $resident->subdivision_id !== $subdivisionId || $resident->status !== 'Active') {
            return [null, null, null];
        }

        return [$resident->resident_id, 'resident_account', now()];
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
            ->map(fn (string $path) => ['path' => $path, 'url' => asset($path)]);
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
        if (!in_array($data['status'] ?? null, ['Resolved', 'Closed'], true)) {
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
}
