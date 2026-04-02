<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentPhoto;
use App\Models\Resident;
use App\Models\Subdivision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filterSubdivision = (int) $request->query('subdivision_id', 0);

        $query = Incident::query()
            ->with(['subdivision', 'verifiedResident', 'proofPhotos'])
            ->orderByDesc('incident_date');

        if (!$user->isAdmin()) {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        $incidents = $query->get();
        $subdivisions = $user->isAdmin()
            ? Subdivision::orderBy('subdivision_name')->get()
            : collect();

        return view('incidents.index', compact('incidents', 'subdivisions', 'filterSubdivision'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $subdivisions = $user->isAdmin()
            ? Subdivision::where('status', 'Active')->orderBy('subdivision_name')->get()
            : collect();
        $effectiveSubdivision = $this->resolveEffectiveSubdivisionId($request);

        return view('incidents.create', compact('subdivisions', 'effectiveSubdivision'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subdivision_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:150'],
            'incident_date' => ['required', 'date'],
            'status' => ['required', 'in:Open,Under Investigation,Resolved,Closed'],
            'verified_resident_id' => ['nullable', 'integer'],
            'verification_method' => ['nullable', 'in:qr_scan,manual_code'],
            'proof_photos' => ['nullable', 'array', 'max:10'],
            'proof_photos.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $subdivisionId = $this->resolveSubmittedSubdivisionId($request);
        if (!$subdivisionId) {
            return back()->withErrors(['subdivision_id' => 'Please select a valid subdivision.'])->withInput();
        }

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

        $proofPhotoPaths = $this->storeProofPhotos($request);

        $incident = Incident::create([
            'subdivision_id' => $subdivisionId,
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'category' => $data['category'] ?: null,
            'location' => $data['location'] ?: null,
            'incident_date' => $data['incident_date'],
            'status' => $data['status'],
            'proof_photo_path' => $proofPhotoPaths[0] ?? null,
            'reported_by' => $request->user()->user_id,
            'verified_resident_id' => $verifiedResidentId,
            'verification_method' => $verificationMethod,
            'verified_at' => $verifiedAt,
        ]);

        foreach ($proofPhotoPaths as $index => $photoPath) {
            IncidentPhoto::create([
                'incident_id' => $incident->incident_id,
                'photo_path' => $photoPath,
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('incidents.index', $this->routeContext($request, $subdivisionId))
            ->with('success', 'Incident reported successfully.');
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
            'address_or_unit' => $resident->address_or_unit ?? '',
        ]);
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
}
