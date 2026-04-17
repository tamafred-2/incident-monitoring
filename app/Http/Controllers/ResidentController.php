<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filterQ = trim((string) $request->query('q', ''));
        $filterStatus = trim((string) $request->query('status', ''));
        $filterSubdivision = (int) $request->query('subdivision_id', 0);

        $query = Resident::query()
            ->with(['subdivision', 'house', 'user'])
            ->orderBy('full_name');

        if ($filterQ !== '') {
            $query->where(function ($builder) use ($filterQ) {
                $builder->where('full_name', 'like', "%{$filterQ}%")
                    ->orWhere('resident_code', 'like', "%{$filterQ}%")
                    ->orWhere('address_or_unit', 'like', "%{$filterQ}%")
                    ->orWhereHas('house', function ($houseQuery) use ($filterQ) {
                        $houseQuery->where('block', 'like', "%{$filterQ}%")
                            ->orWhere('lot', 'like', "%{$filterQ}%");
                    });
            });
        }

        if (in_array($filterStatus, ['Active', 'Inactive'], true)) {
            $query->where('status', $filterStatus);
        }

        if (!$user->isAdmin()) {
            $query->where('subdivision_id', $user->allowedSubdivisionId());
        } elseif ($filterSubdivision) {
            $query->where('subdivision_id', $filterSubdivision);
        }

        $residents = $query->get();
        $subdivisions = $user->isAdmin()
            ? Subdivision::orderBy('subdivision_name')->get()
            : collect();
        $houses = $user->isAdmin()
            ? House::query()->with('subdivision')->orderBy('subdivision_id')->orderBy('block')->orderBy('lot')->get()
            : collect();

        return view('residents.index', compact(
            'residents',
            'subdivisions',
            'houses',
            'filterQ',
            'filterStatus',
            'filterSubdivision'
        ));
    }

    public function show(Request $request, Resident $resident): View
    {
        if (!$request->user()->canAccessSubdivision($resident->subdivision_id)) {
            abort(403);
        }

        $resident->load(['subdivision', 'house', 'user', 'incidents.reporter']);

        return view('residents.show', [
            'resident' => $resident,
            'indexContext' => $this->indexContext($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateResident($request);
        $shouldCreateAccount = $request->filled('account_email') || $request->filled('account_password');
        $accountData = null;

        if ($shouldCreateAccount) {
            $accountData = $request->validate([
                'account_email' => ['required', 'email', 'max:100', 'unique:users,email'],
                'account_password' => ['required', 'string', 'min:8'],
            ]);
        }

        DB::transaction(function () use ($data, $shouldCreateAccount, $accountData): void {
            $resident = Resident::create($data);

            if ($shouldCreateAccount && $accountData !== null) {
                $nameParts = $resident->name_parts;

                User::create([
                    'surname' => $nameParts['surname'],
                    'first_name' => $nameParts['first_name'],
                    'middle_name' => $nameParts['middle_name'],
                    'extension' => $nameParts['extension'],
                    'email' => $accountData['account_email'],
                    'password' => $accountData['account_password'],
                    'requires_password_change' => true,
                    'role' => 'resident',
                    'subdivision_id' => $resident->subdivision_id,
                    'resident_id' => $resident->resident_id,
                ]);
            }
        });

        return redirect()->route('residents.index')
            ->with('success', 'Resident created successfully.');
    }

    public function update(Request $request, Resident $resident): RedirectResponse
    {
        $data = $this->validateResident($request, $resident);

        $resident->update($data);

        return redirect()->route('residents.index')
            ->with('success', 'Resident updated successfully.');
    }

    public function destroy(Request $request, Resident $resident): RedirectResponse
    {
        if ($resident->incidents()->exists()) {
            return redirect()->route('residents.index', $this->indexContext($request))
                ->with('error', 'Residents with verified incident records cannot be deleted.');
        }

        DB::transaction(function () use ($resident): void {
            User::withTrashed()
                ->where('resident_id', $resident->resident_id)
                ->get()
                ->each(function (User $linkedUser): void {
                    if (!$linkedUser->trashed()) {
                        $linkedUser->delete();
                    }
                });

            $resident->delete();
        });

        return redirect()->route('residents.index', $this->indexContext($request))
            ->with('success', 'Resident deleted successfully. Linked resident account(s) were archived.');
    }

    public function qrCard(Request $request, Resident $resident): View|Response
    {
        if (!$request->user()->canAccessSubdivision($resident->subdivision_id)) {
            abort(403);
        }

        $resident->load(['subdivision', 'house']);

        return view('residents.qr-card', [
            'resident' => $resident,
            'qrPayload' => 'RESIDENT:' . $resident->resident_code,
        ]);
    }

    private function validateResident(Request $request, ?Resident $resident = null): array
    {
        $data = $request->validate([
            'surname' => ['required_without:full_name', 'nullable', 'string', 'max:50'],
            'first_name' => ['required_without:full_name', 'nullable', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'extension' => ['nullable', 'string', 'max:20'],
            'full_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'subdivision_id' => ['required', 'integer', 'exists:subdivisions,subdivision_id'],
            'house_id' => ['nullable', 'integer', 'exists:houses,house_id'],
            'address_or_unit' => ['nullable', 'string', 'max:150'],
            'resident_code' => ['nullable', 'string', 'max:64', Rule::unique('residents', 'resident_code')->ignore($resident?->resident_id, 'resident_id')],
            'status' => ['nullable', Rule::in(['Active', 'Inactive'])],
        ]);

        $house = null;

        if (!empty($data['house_id'])) {
            $house = House::query()->find($data['house_id']);

            if (!$house || (int) $house->subdivision_id !== (int) $data['subdivision_id']) {
                throw ValidationException::withMessages([
                    'house_id' => 'The selected house does not belong to the selected subdivision.',
                ]);
            }
        }

        return [
            'subdivision_id' => (int) $data['subdivision_id'],
            'house_id' => $house?->house_id,
            'full_name' => $this->resolveResidentFullName($data),
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'address_or_unit' => $house?->display_address ?? ($data['address_or_unit'] ?: null),
            'status' => $data['status'] ?? $resident?->status ?? 'Active',
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
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
