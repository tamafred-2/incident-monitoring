<?php

namespace App\Http\Controllers;

use App\Models\Subdivision;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubdivisionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterStatus = trim((string) $request->query('status', ''));
        $filterView = trim((string) $request->query('view', 'active'));

        $query = Subdivision::query()->orderBy('subdivision_name');

        if ($user->isAdmin()) {
            if ($filterView === 'deleted') {
                $query->onlyTrashed();
            } elseif ($filterView === 'all') {
                $query->withTrashed();
            }

            if ($filterQ !== '') {
                $query->where(function ($builder) use ($filterQ) {
                    $builder->where('subdivision_name', 'like', "%{$filterQ}%")
                        ->orWhere('address', 'like', "%{$filterQ}%")
                        ->orWhere('contact_person', 'like', "%{$filterQ}%")
                        ->orWhere('contact_number', 'like', "%{$filterQ}%");
                });
            }

            if (in_array($filterStatus, ['Active', 'Inactive'], true)) {
                $query->where('status', $filterStatus);
            }
        } else {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        }

        $subdivisions = $query->get();

        return view('subdivisions.index', compact(
            'subdivisions',
            'filterQ',
            'filterStatus',
            'filterView'
        ));
    }

    public function show(Request $request, Subdivision $subdivision): View
    {
        if (!$request->user()->isAdmin() && !$request->user()->canAccessSubdivision($subdivision->subdivision_id)) {
            abort(403);
        }

        $subdivision->loadCount(['users', 'residents', 'visitors', 'incidents', 'houses']);

        return view('subdivisions.show', [
            'subdivision' => $subdivision,
            'indexContext' => $this->indexContext($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subdivision_name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        Subdivision::create($data);

        return redirect()->route('subdivisions.index')
            ->with('success', 'Subdivision added successfully.');
    }

    public function update(Request $request, Subdivision $subdivision)
    {
        $data = $request->validate([
            'subdivision_name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $subdivision->update($data);

        return redirect()->route('subdivisions.index')
            ->with('success', 'Subdivision updated successfully.');
    }

    public function destroy(Request $request, Subdivision $subdivision)
    {
        $subdivision->delete();

        return redirect()->route('subdivisions.index', $this->indexContext($request))
            ->with('success', 'Subdivision archived successfully.');
    }

    public function restore(Request $request, int $subdivisionId)
    {
        $subdivision = Subdivision::withTrashed()->findOrFail($subdivisionId);

        if (!$subdivision->trashed()) {
            return redirect()->route('subdivisions.index', $this->indexContext($request))
                ->with('error', 'That subdivision is already active.');
        }

        $subdivision->restore();

        return redirect()->route('subdivisions.index', $this->indexContext($request))
            ->with('success', 'Subdivision restored successfully.');
    }

    public function forceDelete(Request $request, int $subdivisionId)
    {
        $subdivision = Subdivision::withTrashed()->findOrFail($subdivisionId);

        if (!$subdivision->trashed()) {
            return redirect()->route('subdivisions.index', $this->indexContext($request))
                ->with('error', 'Only archived subdivisions can be permanently deleted.');
        }

        $subdivision->forceDelete();

        return redirect()->route('subdivisions.index', $this->indexContext($request))
            ->with('success', 'Subdivision permanently deleted.');
    }

    private function indexContext(Request $request): array
    {
        $context = array_filter([
            'q' => $request->input('q', $request->query('q')),
            'status' => $request->input('status', $request->query('status')),
        ], static fn ($value) => $value !== null && $value !== '');

        $view = $request->input('view', $request->query('view', 'active'));
        if ($view !== 'active') {
            $context['view'] = $view;
        }

        return $context;
    }
}
