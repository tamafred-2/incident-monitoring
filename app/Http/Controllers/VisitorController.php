<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\Visitor;
use App\Models\VisitorRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VisitorController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);
        $historyView = $this->resolveHistoryView($request->query('view'));

        $query = Visitor::query()
            ->with('subdivision')
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

        $this->applyHistoryViewScope($query, $historyView);

        if (!$user->isAdmin()) {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        $visitors = $query->get();
        $subdivisions = $user->isAdmin()
            ? Subdivision::where('status', 'Active')->orderBy('subdivision_name')->get()
            : collect();
        $housesBySubdivision = House::query()
            ->select('subdivision_id', 'block', 'lot')
            ->orderBy('block')
            ->orderBy('lot')
            ->get()
            ->groupBy('subdivision_id')
            ->map(fn ($houses) => $houses->map(fn (House $house) => $house->display_address)->values()->all());

        $residentsByHouse = House::query()
            ->with(['residents' => fn ($q) => $q->where('status', 'Active')
                ->whereHas('user')
                ->select('house_id', 'resident_id', 'full_name')])
            ->get()
            ->mapWithKeys(fn (House $house) => [
                $house->display_address => $house->residents->map(fn (Resident $r) => [
                    'id'   => $r->resident_id,
                    'name' => $r->full_name,
                ])->values()->all(),
            ]);
        $effectiveSubdivision = $this->resolveEffectiveSubdivisionId($request);

        $pendingRequests = VisitorRequest::query()
            ->with('resident')
            ->where('status', 'Pending')
            ->when(
                !$user->isAdmin(),
                fn ($q) => $q->where('subdivision_id', $user->allowedSubdivisionId())
            )
            ->when(
                $user->isAdmin() && $filterSubdivision,
                fn ($q) => $q->where('subdivision_id', $filterSubdivision)
            )
            ->orderByDesc('requested_at')
            ->get();

        $insideVisitors = Visitor::query()
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
            ->where('status', 'Inside')
            ->orderByDesc('check_in')
            ->get();

        return view('visitors.index', compact(
            'visitors',
            'subdivisions',
            'filterQ',
            'filterSubdivision',
            'historyView',
            'effectiveSubdivision',
            'insideVisitors',
            'pendingRequests',
            'housesBySubdivision',
            'residentsByHouse',
        ));
    }

    public function show(Request $request, Visitor $visitor): View
    {
        $visitor->load('subdivision');

        return view('visitors.show', [
            'visitor' => $visitor,
            'dashboardQuery' => $request->only(['inside_per_page', 'page']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subdivision_id'        => ['nullable', 'integer'],
            'surname'               => ['required', 'string', 'max:100'],
            'first_name'            => ['required', 'string', 'max:100'],
            'middle_initials'       => ['nullable', 'string', 'max:20'],
            'extension'             => ['nullable', 'string', 'max:20'],
            'phone'                 => ['required', 'string', 'max:40'],
            'plate_number'          => ['nullable', 'string', 'max:30'],
            'id_photo'              => ['required', 'image', 'max:4096'],
            'purpose'               => ['nullable', 'string'],
            'host_employee'         => ['required', 'string', 'max:150'],
            'host_resident_id'      => ['nullable', 'integer'],
            'house_address_or_unit' => ['required', 'string', 'max:120'],
            'resident_code'         => ['nullable', 'string', 'max:20'],
        ]);

        $subdivisionId = $this->resolveSubmittedSubdivisionId($request);
        if (!$subdivisionId) {
            return back()->withErrors(['subdivision_id' => 'Please select a valid subdivision.'])->withInput();
        }

        if (!empty($data['house_address_or_unit'])) {
            $normalizedAddress = strtoupper(trim($data['house_address_or_unit']));

            $houseExists = House::query()
                ->where('subdivision_id', $subdivisionId)
                ->get()
                ->contains(fn (House $house) => strtoupper($house->display_address) === $normalizedAddress);

            if (!$houseExists) {
                return back()->withErrors([
                    'house_address_or_unit' => 'Please select a valid house / unit for the chosen subdivision.',
                ])->withInput();
            }
        }

        $photoPath = null;
        if ($request->hasFile('id_photo')) {
            $photoPath = $request->file('id_photo')->store('visitor-ids', 'public');
        }

        // Check if resident code was provided for instant check-in bypass
        $residentCode = Resident::normalizeResidentCode($data['resident_code'] ?? null);
        if ($residentCode) {
            $resident = Resident::where('resident_code', $residentCode)
                ->where('subdivision_id', $subdivisionId)
                ->first();

            if (!$resident) {
                return back()->withErrors(['resident_code' => 'Invalid resident code for this subdivision.'])->withInput();
            }

            Visitor::create([
                'subdivision_id'        => $subdivisionId,
                'surname'               => $data['surname'],
                'first_name'            => $data['first_name'],
                'middle_initials'       => $data['middle_initials'] ?? null,
                'extension'             => $data['extension'] ?? null,
                'phone'                 => $data['phone'],
                'plate_number'          => $data['plate_number'] ?? null,
                'id_photo_path'         => $photoPath,
                'purpose'               => $data['purpose'] ?? null,
                'host_employee'         => $data['host_employee'],
                'house_address_or_unit' => $data['house_address_or_unit'] ?? null,
                'check_in'              => now(),
                'check_out'             => null,
                'status'                => 'Inside',
            ]);

            return redirect()->route('visitors.index', $this->visitorRouteContext($request, $subdivisionId))
                ->with('success', 'Visitor checked in immediately using resident code.');
        }

        // No resident code — find the selected resident by host_resident_id and create a pending request
        $resident = isset($data['host_resident_id'])
            ? Resident::where('resident_id', $data['host_resident_id'])
                ->where('subdivision_id', $subdivisionId)
                ->where('status', 'Active')
                ->whereHas('user')
                ->first()
            : null;

        if (!$resident) {
            return back()->withErrors(['host_employee' => 'The selected resident was not found or does not have an account.'])->withInput();
        }

        VisitorRequest::create([
            'resident_id'           => $resident->resident_id,
            'subdivision_id'        => $subdivisionId,
            'visitor_name'          => Visitor::formatFullName($data['first_name'], $data['middle_initials'] ?? null, $data['surname'], $data['extension'] ?? null),
            'surname'               => $data['surname'],
            'first_name'            => $data['first_name'],
            'middle_initials'       => $data['middle_initials'] ?? null,
            'extension'             => $data['extension'] ?? null,
            'phone'                 => $data['phone'],
            'plate_number'          => $data['plate_number'] ?? null,
            'id_photo_path'         => $photoPath,
            'house_address_or_unit' => $data['house_address_or_unit'] ?? null,
            'purpose'               => $data['purpose'] ?? null,
            'status'                => 'Pending',
            'requested_at'          => now(),
        ]);

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $subdivisionId))
            ->with('success', 'Visitor request sent to resident for approval.');
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
        if (!in_array($tab, ['check-in', 'check-out', 'history', 'pending'], true)) {
            $tab = 'history';
        }

        $context = [
            'tab' => $tab,
            'view' => $this->resolveHistoryView($request->input('view', $request->query('view'))),
        ];

        $filterQ = trim((string) $request->input('q', $request->query('q', '')));
        if ($filterQ !== '') {
            $context['q'] = $filterQ;
        }

        if ($request->user()->isAdmin() && $subdivisionId) {
            $context['subdivision_id'] = (int) $subdivisionId;
        }

        return $context;
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
}
