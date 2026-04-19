<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Subdivision;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubdivisionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $subdivision = $user->isAdmin()
            ? Subdivision::first()
            : Subdivision::find($user->allowedSubdivisionId());

        if (!$subdivision) {
            abort(404);
        }

        return redirect()->route('subdivisions.show', $subdivision);
    }

    public function show(Request $request, Subdivision $subdivision): View
    {
        if (!$request->user()->isAdmin() && !$request->user()->canAccessSubdivision($subdivision->subdivision_id)) {
            abort(403);
        }

        $filterQ = trim((string) $request->query('q', ''));

        $housesQuery = $subdivision->houses()->with('residents');

        if ($filterQ !== '') {
            $housesQuery->where(function ($builder) use ($filterQ) {
                $builder->where('block', 'like', "%{$filterQ}%")
                    ->orWhere('lot', 'like', "%{$filterQ}%");
            });
        }

        $houses = $housesQuery->orderBy('block')->orderBy('lot')->get();

        $subdivision->loadCount(['users', 'residents', 'visitors', 'incidents']);

        return view('subdivisions.show', compact('subdivision', 'houses', 'filterQ'));
    }

    public function edit(Subdivision $subdivision): View
    {
        return view('subdivisions.edit', compact('subdivision'));
    }

    public function update(Request $request, Subdivision $subdivision)
    {
        $data = $request->validate([
            'subdivision_name'         => ['required', 'string', 'max:150'],
            'country'                  => ['required', 'string', 'max:100'],
            'street'                   => ['required', 'string', 'max:255'],
            'city'                     => ['required', 'string', 'max:100'],
            'province'                 => ['required', 'string', 'max:100'],
            'zip'                      => ['required', 'string', 'max:20'],
            'latitude'                 => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'                => ['nullable', 'numeric', 'between:-180,180'],
            'contact_person'           => ['required', 'string', 'max:100'],
            'contact_number'           => ['required', 'string', 'max:20'],
            'email'                    => ['required', 'email', 'max:100'],
            'secondary_contact_person' => ['nullable', 'string', 'max:100'],
            'secondary_contact_number' => ['nullable', 'string', 'max:20'],
            'secondary_email'          => ['nullable', 'email', 'max:100'],
            'status'                   => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $subdivision->update($data);
        return redirect()->route('subdivisions.show', $subdivision)
            ->with('success', 'Subdivision updated successfully.');
    }
}
