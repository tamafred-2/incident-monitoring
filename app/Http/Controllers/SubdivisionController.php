<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Subdivision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $perPage = $this->resolvePerPageChoice(
            $request->query('per_page_custom'),
            $request->query('per_page'),
            10
        );

        $housesQuery = $subdivision->houses()->with('residents');

        if ($filterQ !== '') {
            $housesQuery->where(function ($builder) use ($filterQ) {
                $builder->where('block', 'like', "%{$filterQ}%")
                    ->orWhere('lot', 'like', "%{$filterQ}%")
                    ->orWhere('street', 'like', "%{$filterQ}%");
            });
        }

        $houses = $housesQuery
            ->orderBy('block')
            ->orderBy('lot')
            ->paginate($perPage)
            ->withQueryString();

        $houseCount = $subdivision->houses()->count();
        $residentCount = $subdivision->residents()->count();

        return view('subdivisions.show', compact(
            'subdivision',
            'houses',
            'filterQ',
            'perPage',
            'houseCount',
            'residentCount'
        ));
    }

    public function logo(Subdivision $subdivision): BinaryFileResponse
    {
        if (!$subdivision->logo_path || !Storage::disk('public')->exists($subdivision->logo_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($subdivision->logo_path));
    }

    public function edit(Subdivision $subdivision): RedirectResponse
    {
        return redirect()
            ->route('subdivisions.show', ['subdivision' => $subdivision, 'edit' => 1]);
    }

    public function update(Request $request, Subdivision $subdivision)
    {
        $data = $request->validate([
            'subdivision_name'         => ['required', 'string', 'max:150'],
            'logo'                     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo'              => ['nullable', 'boolean'],
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

        if ($request->boolean('remove_logo') && $subdivision->logo_path) {
            Storage::disk('public')->delete($subdivision->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($subdivision->logo_path) {
                Storage::disk('public')->delete($subdivision->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('subdivision-logos', 'public');
        }

        unset($data['logo'], $data['remove_logo']);

        $subdivision->update($data);
        return redirect()->route('subdivisions.show', $subdivision)
            ->with('success', 'Subdivision updated successfully.');
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
