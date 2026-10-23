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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterStatus = $user->isAdmin() ? trim((string) $request->query('status', '')) : '';
        $filterSubdivision = (int) $request->query('subdivision_id', 0);
        $perPage = 10;

        $query = Resident::query()
            ->with(['subdivision', 'house'])
            ->orderBy('full_name');

        if ($request->filled('relation_to_owner')) {
            $relation = (string) $request->query('relation_to_owner');
            $query->where(function ($builder) use ($relation) {
                $builder->where('relation_to_owner', $relation);
                if ($relation === 'Unspecified') {
                    $builder->orWhereNull('relation_to_owner')->orWhere('relation_to_owner', '');
                }
            });
        }

        if ($filterQ !== '') {
            $query->where(function ($builder) use ($filterQ) {
                $builder->where('full_name', 'like', "%{$filterQ}%")
                    ->orWhere('address_or_unit', 'like', "%{$filterQ}%")
                    ->orWhereHas('house', function ($houseQuery) use ($filterQ) {
                        $houseQuery->where('block', 'like', "%{$filterQ}%")
                            ->orWhere('lot', 'like', "%{$filterQ}%");
                    });
            });
        }

        if (in_array($filterStatus, ActiveStatus::values(), true)) {
            $query->where('status', $filterStatus);
        }

        if (! $user->isAdmin()) {
            $query->where('status', ActiveStatus::Active->value);
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        $residents = $query
            ->orderBy('resident_id')->simplePaginate($perPage)
            ->withQueryString();
        $modalResidents = collect($residents->items());
        if ($user->isAdmin() && $request->session()->has('errors') && $request->old('edit_resident_id')) {
            $editingResident = Resident::with(['subdivision', 'house'])->find($request->old('edit_resident_id'));
            if ($editingResident && ! $modalResidents->contains('resident_id', $editingResident->resident_id)) {
                $modalResidents = $modalResidents->push($editingResident);
            }
        }
        $subdivisions = $user->isAdmin()
            ? Subdivision::orderBy('subdivision_name')->get()
            : collect();
        $houses = $user->isAdmin()
            ? House::query()->with('subdivision')->orderBy('subdivision_id')->orderBy('block')->orderBy('lot')->get()
            : collect();

        return $this->scrollView('residents.index', compact(
            'residents',
            'modalResidents',
            'subdivisions',
            'houses',
            'filterQ',
            'filterStatus',
            'filterSubdivision',
            'perPage'
        ), ['residents' => 'residents']);
    }

    public function show(Request $request, Resident $resident): View
    {
        if (! $request->user()->isAdmin() && $resident->status !== ActiveStatus::Active) {
            abort(404);
        }

        if (! $request->user()->canAccessSubdivision($resident->subdivision_id)) {
            abort(403);
        }

        $resident->load(['subdivision', 'house']);

        return view('residents.show', [
            'resident' => $resident,
            'ownershipTransfer' => DB::table('house_ownership_transfers')->where('from_resident_id', $resident->getKey())->first(),
            'successors' => $request->user()->isAdmin() && $resident->is_deceased && $resident->house_id
                ? Resident::where('house_id', $resident->house_id)->where('is_deceased', false)
                    ->where('status', ActiveStatus::Active->value)
                    ->where(fn ($q) => $q->whereNull('relation_to_owner')->orWhere('relation_to_owner', '!=', 'Owner'))
                    ->orderBy('full_name')->get()
                : collect(),
            'indexContext' => $this->indexContext($request),
        ]);
    }

    public function transferOwnership(Request $request, Resident $resident): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'successor_id' => ['required', 'integer'],
            'confirm_transfer' => ['accepted'],
        ]);
        DB::transaction(function () use ($request, $resident, $data): void {
            $this->assignOwnership($request, $resident, (int) $data['successor_id']);
        });
        return redirect()->route('residents.show', $resident)->with('success', 'House ownership transferred successfully.');
    }

    private function assignOwnership(Request $request, Resident $resident, int $successorId, bool $transition = false): void
    {
            $house = House::whereKey($resident->house_id)->lockForUpdate()->first();
            $previous = Resident::whereKey($resident->getKey())->lockForUpdate()->firstOrFail();
            $fail = fn ($message) => throw ValidationException::withMessages(['successor_id' => $message]);
            if (! $house || $previous->house_id !== $house->getKey() || ! $previous->is_deceased || (! $transition && $previous->relation_to_owner !== 'Owner')) {
                $fail('Only a deceased owner with an assigned house can have ownership transferred.');
            }
            if (DB::table('house_ownership_transfers')->where('from_resident_id', $previous->getKey())->exists()) {
                $fail('Ownership has already been transferred from this resident.');
            }
            $next = Resident::whereKey($successorId)->where('house_id', $house->getKey())
                ->where('is_deceased', false)->where('status', ActiveStatus::Active->value)->lockForUpdate()->first();
            if (! $next || $next->relation_to_owner === 'Owner') {
                $fail('Choose a living, active resident of this house who is not already an owner.');
            }
            if (Resident::where('house_id', $house->getKey())->where('relation_to_owner', 'Owner')->where('is_deceased', false)->count() >= 2) {
                $fail('This house already has two living owners.');
            }
            DB::table('house_ownership_transfers')->insert([
                'house_id' => $house->getKey(), 'from_resident_id' => $previous->getKey(), 'to_resident_id' => $next->getKey(),
                'previous_owner_name' => $previous->full_name, 'new_owner_name' => $next->full_name,
                'previous_relationship' => $next->relation_to_owner,
                'confirmed_by' => $request->user()->getKey(), 'created_at' => now(),
            ]);
            $next->update(['relation_to_owner' => 'Owner']);
            if ($previous->relation_to_owner === 'Owner') {
                $previous->update(['relation_to_owner' => 'Former Owner']);
            }
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $this->validateResident($request);
            Resident::create($data);
        });

        return redirect()->route('residents.index')
            ->with('success', 'Resident created successfully.');
    }

    public function update(Request $request, Resident $resident): RedirectResponse
    {
        DB::transaction(function () use ($request, $resident): void {
            House::whereKey($resident->house_id)->lockForUpdate()->first();
            $resident = Resident::whereKey($resident->getKey())->lockForUpdate()->firstOrFail();
            $data = $this->validateResident($request, $resident);
            $transition = $resident->relation_to_owner === 'Owner' && $data['is_deceased'];
            if ($transition) {
                if (! $data['relation_to_owner'] || $data['relation_to_owner'] === 'Owner') {
                    throw ValidationException::withMessages(['relation_to_owner' => "Change the deceased owner's relationship, for example to Former Owner."]);
                }
                if ((int) $data['house_id'] !== (int) $resident->house_id) {
                    throw ValidationException::withMessages(['house_id' => 'Keep the same house while transferring ownership.']);
                }
                $request->validate(['successor_id' => ['required', 'integer'], 'confirm_transfer' => ['accepted']]);
            }
            $resident->update($data);
            if ($transition) {
                $this->assignOwnership($request, $resident, (int) $request->input('successor_id'), true);
            }
        });

        return redirect()->route('residents.index')
            ->with('success', 'Resident updated successfully.');
    }

    private function validateResident(Request $request, ?Resident $resident = null): array
    {
        $data = $request->validate([
            'surname' => ['required_without:full_name', 'nullable', 'string', 'max:50', new PersonName],
            'first_name' => ['required_without:full_name', 'nullable', 'string', 'max:50', new PersonName],
            'middle_name' => ['nullable', 'string', 'max:50', new PersonName],
            'extension' => ['nullable', 'string', 'max:20', new PersonName],
            'full_name' => ['nullable', 'string', 'max:100', new PersonName],
            'phone' => ['required', 'string', 'digits:11'],
            'email' => ['required', 'email', 'max:100'],
            'subdivision_id' => ['required', 'integer', 'exists:subdivisions,subdivision_id'],
            'house_id' => ['nullable', 'integer', 'exists:houses,house_id'],
            'address_or_unit' => ['nullable', 'string', 'max:150'],
            'relation_to_owner' => ['nullable', 'string', 'max:50'],
            'relation_to_owner_other' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::enum(ActiveStatus::class)],
            'is_deceased' => ['sometimes', 'boolean'],
        ]);

        $relation = trim((string) ($data['relation_to_owner'] ?? ''));
        $relationOther = trim((string) ($data['relation_to_owner_other'] ?? ''));

        if ($relation === 'Other') {
            if ($relationOther === '') {
                throw ValidationException::withMessages([
                    'relation_to_owner_other' => 'Please specify the relation.',
                ]);
            }

            $relation = $relationOther;
        }

        if ($relation !== '') {
            $allowedRelations = ['Owner', 'Husband', 'Wife', 'Child', 'Relative', 'Friend', 'Tenant', 'Helper'];
            if (! in_array($relation, $allowedRelations, true)) {
                $relation = Str::title($relation);
            }
        }

        $house = null;

        if (! empty($data['house_id'])) {
            $house = House::query()->lockForUpdate()->find($data['house_id']);

            if (! $house || (int) $house->subdivision_id !== (int) $data['subdivision_id']) {
                throw ValidationException::withMessages([
                    'house_id' => 'The selected house does not belong to the selected subdivision.',
                ]);
            }
        }

        $deceased = $request->has('is_deceased') ? $request->boolean('is_deceased') : (bool) $resident?->is_deceased;
        if ($deceased && $relation === 'Owner') {
            throw ValidationException::withMessages(['relation_to_owner' => 'A deceased resident cannot remain Owner. Select another relationship, such as Former Owner.']);
        }
        if ($relation === 'Owner' && $house && ! $deceased) {
            $ownerCount = Resident::query()->where('house_id', $house->house_id)
                ->where('relation_to_owner', 'Owner')->where('is_deceased', false)
                ->when($resident, fn ($query) => $query->whereKeyNot($resident->getKey()))->count();
            if ($ownerCount >= 2) {
                throw ValidationException::withMessages(['relation_to_owner' => 'A house can have at most two living owners.']);
            }
        }

        return [
            'subdivision_id' => (int) $data['subdivision_id'],
            'house_id' => $house?->house_id,
            'full_name' => $this->resolveResidentFullName($data),
            'phone' => $data['phone'],
            'email' => $data['email'],
            'address_or_unit' => $house?->display_address ?? ($data['address_or_unit'] ?: null),
            'relation_to_owner' => $relation !== '' ? $relation : null,
            'is_deceased' => $deceased,
            'status' => $deceased ? ActiveStatus::Inactive->value : ($data['status'] ?? $resident?->status ?? ActiveStatus::Active->value),
        ];
    }

    private function resolveResidentFullName(array $data): string
    {
        $formattedName = User::formatFullName(
            $data['first_name'] ?? null,
            $data['surname'] ?? null,
            $data['middle_name'] ?? null,
            $data['extension'] ?? null
        );

        if ($formattedName !== '') {
            return $formattedName;
        }

        return trim((string) ($data['full_name'] ?? ''));
    }

    private function indexContext(Request $request): array
    {
        return array_filter([
            'q' => $request->input('q', $request->query('q')),
            'status' => $request->input('status', $request->query('status')),
            'subdivision_id' => $request->input('subdivision_id', $request->query('subdivision_id')),
            'per_page' => $this->resolvePerPageChoice(
                $request->input('per_page_custom', $request->query('per_page_custom')),
                $request->input('per_page', $request->query('per_page')),
                10
            ),
        ], static fn ($value) => $value !== null && $value !== '');
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
