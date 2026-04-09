<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Subdivision;
use App\Models\Visitor;
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
                    ->orWhere('id_number', 'like', "%{$filterQ}%")
                    ->orWhere('company', 'like', "%{$filterQ}%")
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
                            ->orWhere('id_number', 'like', "%{$filterQ}%")
                            ->orWhere('company', 'like', "%{$filterQ}%")
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
            'housesBySubdivision',
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
            'subdivision_id' => ['nullable', 'integer'],
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_initials' => ['nullable', 'string', 'max:20'],
            'extension' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:40'],
            'id_number' => ['nullable', 'string', 'max:80'],
            'company' => ['nullable', 'string', 'max:150'],
            'purpose' => ['nullable', 'string'],
            'host_employee' => ['nullable', 'string', 'max:150'],
            'house_address_or_unit' => ['nullable', 'string', 'max:120'],
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

        Visitor::create([
            'subdivision_id' => $subdivisionId,
            'surname' => $data['surname'],
            'first_name' => $data['first_name'],
            'middle_initials' => $data['middle_initials'] ?? null,
            'extension' => $data['extension'] ?? null,
            'phone' => $data['phone'] ?? null,
            'id_number' => $data['id_number'] ?? null,
            'company' => $data['company'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'host_employee' => $data['host_employee'] ?? null,
            'house_address_or_unit' => $data['house_address_or_unit'] ?? null,
            'check_in' => now(),
            'check_out' => null,
            'status' => 'Inside',
        ]);

        return redirect()->route('visitors.index', $this->visitorRouteContext($request, $subdivisionId))
            ->with('success', 'Visitor checked in successfully.');
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
