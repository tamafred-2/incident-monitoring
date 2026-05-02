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
use Illuminate\View\View;

class VisitorController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);
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

        if (!$user->isAdmin()) {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        $visitors = $query
            ->paginate($historyPerPage, ['*'], 'history_page')
            ->withQueryString();
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
                ->select('house_id', 'resident_id', 'full_name', 'phone')])
            ->get()
            ->mapWithKeys(fn (House $house) => [
                $house->display_address => $house->residents->map(fn (Resident $r) => [
                    'id'    => $r->resident_id,
                    'name'  => $r->full_name,
                    'phone' => $r->phone,
                ])->values()->all(),
            ]);
        $effectiveSubdivision = $this->resolveEffectiveSubdivisionId($request);

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
            'purpose'               => ['nullable', 'string'],
            'host_employee'         => ['required', 'string', 'max:150'],
            'house_address_or_unit' => ['required', 'string', 'max:120'],
            'resident_id'           => ['required', 'integer'],
        ]);

        $subdivisionId = $this->resolveSubmittedSubdivisionId($request);
        if (!$subdivisionId) {
            return back()->withErrors(['subdivision_id' => 'Please select a valid subdivision.'])->withInput();
        }

        $normalizedAddress = strtoupper(trim($data['house_address_or_unit']));

        $house = House::query()
            ->where('subdivision_id', $subdivisionId)
            ->get()
            ->first(fn (House $house) => strtoupper($house->display_address) === $normalizedAddress);

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

        VisitorRequest::create([
            'visitor_id'            => null,
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
            'plate_number'          => null,
            'id_photo_path'         => null,
            'purpose'               => $data['purpose'] ?? null,
            'house_address_or_unit' => $house->display_address,
            'status'                => 'Pending',
            'requested_at'          => now(),
            'responded_at'          => null,
        ]);

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $subdivisionId))
            ->with('success', 'Visitor request submitted. Contact the resident using the registered phone number in the system or wait for automated response before allowing entry.');
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
}
