<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Subdivision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HouseController extends Controller
{
    public function index(Request $request): View
    {
        $filterQ = trim((string) $request->query('q', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);

        $query = House::query()
            ->with('subdivision')
            ->orderBy('subdivision_id')
            ->orderBy('block')
            ->orderBy('lot');

        if ($filterQ !== '') {
            $query->where(function ($builder) use ($filterQ) {
                $builder->where('block', 'like', "%{$filterQ}%")
                    ->orWhere('lot', 'like', "%{$filterQ}%")
                    ->orWhereHas('subdivision', fn ($subdivisionQuery) => $subdivisionQuery->where('subdivision_name', 'like', "%{$filterQ}%"));
            });
        }

        if ($filterSubdivision > 0) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        $houses = $query->get();
        $subdivisions = Subdivision::query()
            ->where('status', 'Active')
            ->orderBy('subdivision_name')
            ->get();

        return view('houses.index', compact('houses', 'subdivisions', 'filterQ', 'filterSubdivision'));
    }

    public function show(Request $request, House $house): View
    {
        $house->load(['subdivision', 'residents']);

        return view('houses.show', [
            'house' => $house,
            'indexContext' => $this->indexContext($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateHouse($request);

        House::create($data);

        return redirect()->route('houses.index')
            ->with('success', 'House added successfully.');
    }

    public function update(Request $request, House $house): RedirectResponse
    {
        $data = $this->validateHouse($request, $house);

        $house->update($data);

        return redirect()->route('houses.index')
            ->with('success', 'House updated successfully.');
    }

    public function destroy(Request $request, House $house): RedirectResponse
    {
        $house->delete();

        return redirect()->route('houses.index', $this->indexContext($request))
            ->with('success', 'House deleted successfully.');
    }

    private function validateHouse(Request $request, ?House $house = null): array
    {
        $data = $request->validate([
            'subdivision_id' => ['required', 'integer', 'exists:subdivisions,subdivision_id'],
            'block' => [
                'required',
                'string',
                'max:30',
                Rule::unique('houses', 'block')
                    ->where(function ($query) use ($request) {
                        $query->where('subdivision_id', $request->integer('subdivision_id'))
                            ->where('lot', $this->normalizeAddressPart((string) $request->input('lot')));
                    })
                    ->ignore($house?->house_id, 'house_id'),
            ],
            'lot' => ['required', 'string', 'max:30'],
        ]);

        return [
            'subdivision_id' => (int) $data['subdivision_id'],
            'block' => $this->normalizeAddressPart($data['block']),
            'lot' => $this->normalizeAddressPart($data['lot']),
        ];
    }

    private function normalizeAddressPart(string $value): string
    {
        return strtoupper(trim($value));
    }

    private function indexContext(Request $request): array
    {
        return array_filter([
            'q' => $request->input('q', $request->query('q')),
            'subdivision_id' => $request->input('subdivision_id', $request->query('subdivision_id')),
        ], static fn ($value) => $value !== null && $value !== '' && $value !== 0 && $value !== '0');
    }
}
