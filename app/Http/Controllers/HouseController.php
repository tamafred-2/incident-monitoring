<?php

namespace App\Http\Controllers;

use App\Enums\ActiveStatus;
use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Rules\PersonName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HouseController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filterQ = trim((string) $request->query('q', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);

        $query = House::query()
            ->withExists('incidents')
            ->with([
                'subdivision',
                'residents' => fn ($residentQuery) => $residentQuery->select('resident_id', 'house_id', 'full_name', 'relation_to_owner', 'status')->orderBy('full_name'),
            ])
            ->orderBy('subdivision_id')
            ->orderByRaw('CAST(block AS INTEGER)')
            ->orderByRaw('CAST(lot AS INTEGER)')
            ->orderBy('street');

        if ($filterQ !== '') {
            $query->where(function ($builder) use ($filterQ) {
                $builder->where('block', 'like', "%{$filterQ}%")
                    ->orWhere('lot', 'like', "%{$filterQ}%")
                    ->orWhere('street', 'like', "%{$filterQ}%")
                    ->orWhereHas('subdivision', fn ($subdivisionQuery) => $subdivisionQuery->where('subdivision_name', 'like', "%{$filterQ}%"));
            });
        }

        if ($filterSubdivision > 0) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        $houses = $query->orderBy('house_id')->simplePaginate(10)->withQueryString();
        $subdivisions = Subdivision::query()
            ->where('status', ActiveStatus::Active)
            ->orderBy('subdivision_name')
            ->get();

        return $this->scrollView('houses.index', compact('houses', 'subdivisions', 'filterQ', 'filterSubdivision'), ['houses' => 'houses']);
    }

    public function show(Request $request, House $house): View
    {
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->canAccessSubdivision($house->subdivision_id)) {
            abort(403);
        }

        $house->load([
            'subdivision',
            'residents' => fn ($query) => $query->orderBy('full_name'),
        ]);

        $showSubdivisionBackLink = $request->boolean('from_subdivision') || ! $user->isAdmin();
        $subdivisionContext = array_filter([
            'subdivision' => $house->subdivision_id,
            'q' => $request->query('q'),
            'per_page' => $request->query('per_page'),
            'page' => $request->query('page'),
        ], static fn ($value) => $value !== null && $value !== '');

        return view('houses.show', [
            'house' => $house,
            'indexContext' => $this->indexContext($request),
            'backUrl' => $showSubdivisionBackLink
                ? route('subdivisions.show', $subdivisionContext)
                : route('houses.index', $this->indexContext($request)),
            'backLabel' => $showSubdivisionBackLink
                ? ($user->isSecurity() ? 'Back to Contacts' : 'Back to House Management')
                : 'Back to Houses',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateHouse($request, null, 'houseCreate');

        DB::transaction(function () use ($data): void {
            $owner = $data['owner'];
            unset($data['owner']);
            $house = House::create($data);

            Resident::create([
                'subdivision_id' => $house->subdivision_id,
                'house_id' => $house->house_id,
                'full_name' => $owner['full_name'],
                'phone' => $owner['phone'],
                'email' => $owner['email'],
                'address_or_unit' => $house->display_address,
                'relation_to_owner' => 'Owner',
                'status' => ActiveStatus::Active,
            ]);
        });

        $redirect = $request->input('_redirect');

        return redirect($redirect ?? route('houses.index'))
            ->with('success', 'House added successfully.');
    }

    public function update(Request $request, House $house): RedirectResponse
    {
        $data = $this->validateHouse($request, $house, 'houseEdit');

        $house->update($data);

        $redirect = $request->input('_redirect');

        return redirect($redirect ?? route('houses.index'))
            ->with('success', 'House updated successfully.');
    }

    public function destroy(Request $request, House $house): RedirectResponse
    {
        $redirect = $request->input('_redirect');

        if (! $house->canDelete()) {
            return redirect($redirect ?? route('houses.index', $this->indexContext($request)))
                ->with('error', 'This house cannot be deleted because it has linked residents or incident records. Use Edit to update the house instead.');
        }

        $house->delete();

        return redirect($redirect ?? route('houses.index', $this->indexContext($request)))
            ->with('success', 'House deleted successfully.');
    }

    private function validateHouse(Request $request, ?House $house = null, string $errorBag = 'default'): array
    {
        $request->merge([
            'street' => $this->normalizeStreet((string) $request->input('street')),
            'block' => House::normalizeBlock((string) $request->input('block')),
            'lot' => House::normalizeLot((string) $request->input('lot')),
        ]);

        $rules = [
            'subdivision_id' => ['required', 'integer', 'exists:subdivisions,subdivision_id'],
            'street' => ['required', 'string', 'max:120'],
            'block' => ['required', 'string', 'max:30'],
            'lot' => [
                'required',
                'string',
                'max:30',
                Rule::unique('houses', 'lot')
                    ->where(function ($query) use ($request) {
                        $normalizedStreet = $this->normalizeStreet((string) $request->input('street'));

                        $query->where('subdivision_id', $request->integer('subdivision_id'))
                            ->whereRaw('LOWER(street) = ?', [mb_strtolower($normalizedStreet)])
                            ->where('block', House::normalizeBlock((string) $request->input('block')));
                    })
                    ->ignore($house?->house_id, 'house_id'),
            ],
        ];

        if ($house === null) {
            $rules += [
                'owner.surname' => ['required', 'string', 'max:50', new PersonName],
                'owner.first_name' => ['required', 'string', 'max:50', new PersonName],
                'owner.middle_name' => ['nullable', 'string', 'max:50', new PersonName],
                'owner.extension' => ['nullable', 'string', 'max:20', new PersonName],
                'owner.phone' => ['required', 'string', 'digits:11'],
                'owner.email' => ['required', 'email', 'max:100'],
            ];
        }

        $validator = Validator::make($request->all(), $rules, [
            'lot.unique' => 'This lot already exists for the same street and block.',
        ]);
        $validator->after(function ($validator) use ($request, $house): void {
            if ($house === null && ! $validator->errors()->any()) {
                $name = User::formatFullName(
                    $request->input('owner.first_name'),
                    $request->input('owner.surname'),
                    $request->input('owner.middle_name'),
                    $request->input('owner.extension'),
                );
                if (mb_strlen($name) > 100) {
                    $validator->errors()->add('owner.surname', 'The owner’s full name must not exceed 100 characters.');
                }
            }
        });
        $data = $validator->validateWithBag($errorBag);

        $houseData = [
            'subdivision_id' => (int) $data['subdivision_id'],
            'street' => $this->normalizeStreet((string) $data['street']),
            'block' => House::normalizeBlock($data['block']),
            'lot' => House::normalizeLot($data['lot']),
        ];

        if ($house === null) {
            $houseData['owner'] = $data['owner'];
            $houseData['owner']['full_name'] = User::formatFullName(
                $data['owner']['first_name'],
                $data['owner']['surname'],
                $data['owner']['middle_name'] ?? null,
                $data['owner']['extension'] ?? null,
            );
        }

        return $houseData;
    }

    private function normalizeStreet(string $value): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return Str::title(mb_strtolower($normalized));
    }

    private function indexContext(Request $request): array
    {
        return array_filter([
            'q' => $request->input('q', $request->query('q')),
            'subdivision_id' => $request->input('subdivision_id', $request->query('subdivision_id')),
        ], static fn ($value) => $value !== null && $value !== '' && $value !== 0 && $value !== '0');
    }
}
