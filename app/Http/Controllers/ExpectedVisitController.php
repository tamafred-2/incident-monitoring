<?php

namespace App\Http\Controllers;

use App\Enums\ActiveStatus;
use App\Models\ExpectedVisit;
use App\Models\Resident;
use App\Models\VisitorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpectedVisitController extends Controller
{
    public function index(Request $request)
    {
        $sortOptions = [
            'name_asc' => ['name', 'asc'], 'name_desc' => ['name', 'desc'],
            'newest' => ['created_at', 'desc'], 'oldest' => ['created_at', 'asc'],
        ];
        $sort = (string) $request->query('sort', 'name_asc');
        if (! isset($sortOptions[$sort])) $sort = 'name_asc';
        [$column, $direction] = $sortOptions[$sort];
        $orderColumn = $column === 'name'
            ? VisitorProfile::select('full_name')->whereColumn('profile_id', 'expected_visits.visitor_profile_id')->limit(1)
            : 'expected_visits.created_at';

        $visits = ExpectedVisit::with(['profile', 'resident.house', 'recorder'])
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('subdivision_id', $request->user()->allowedSubdivisionId()))
            ->where('status', 'Expected')->orderBy($orderColumn, $direction)
            ->orderBy('expected_visits.id', $direction)->paginate(15)->withQueryString();

        return view('expected-visits.index', compact('visits', 'sort'));
    }

    public function create(Request $request, VisitorProfile $profile)
    {
        abort_unless($request->user()->isSecurity(), 403);
        abort_unless($request->user()->canAccessSubdivision($profile->subdivision_id), 403);
        return redirect()->route('visitor-profiles.show', ['profile' => $profile, 'schedule' => 1]);
    }

    public function store(Request $request, VisitorProfile $profile)
    {
        abort_unless($request->user()->isSecurity(), 403);
        abort_unless($request->user()->canAccessSubdivision($profile->subdivision_id), 403);
        $data = $request->validate([
            'resident_id' => ['required', 'integer'],
            'expected_at' => ['required', 'date', 'after:now'],
            'purpose' => ['nullable', 'string', 'max:2000'],
        ]);
        $resident = Resident::whereKey($data['resident_id'])->where('subdivision_id', $profile->subdivision_id)
            ->where('status', ActiveStatus::Active)->whereHas('house')->first();
        if (! $resident) {
            throw ValidationException::withMessages(['resident_id' => 'Select an active resident with a house in this subdivision.']);
        }
        DB::transaction(function () use ($profile, $data, $request) {
            VisitorProfile::whereKey($profile->getKey())->lockForUpdate()->firstOrFail();
            if (ExpectedVisit::where('visitor_profile_id', $profile->getKey())->where('status', 'Expected')->exists()) {
                throw ValidationException::withMessages(['expected_at' => 'This visitor already has an expected visit. Check it in or cancel it before scheduling another.']);
            }
            ExpectedVisit::create($data + [
                'visitor_profile_id' => $profile->getKey(), 'subdivision_id' => $profile->subdivision_id,
                'recorded_by' => $request->user()->getKey(), 'status' => 'Expected',
            ]);
        });

        return redirect()->route('expected-visits.index')->with('success', 'Expected visit recorded. Check the visitor in when they arrive.');
    }

    public function cancel(Request $request, ExpectedVisit $expectedVisit)
    {
        abort_unless($request->user()->canAccessSubdivision($expectedVisit->subdivision_id), 403);
        $changed = ExpectedVisit::whereKey($expectedVisit->getKey())->where('status', 'Expected')->update(['status' => 'Cancelled']);

        return back()->with($changed ? 'success' : 'error', $changed ? 'Expected visit cancelled.' : 'This visit is no longer expected.');
    }
}
