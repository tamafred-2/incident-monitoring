<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filterQ = trim((string) $request->query('q', ''));
        $filterRole = trim((string) $request->query('role', ''));
        $filterSubdivision = $request->query('subdivision_id');
        $filterView = trim((string) $request->query('view', 'active'));
        $perPage = $this->resolvePerPageChoice(
            $request->query('per_page_custom'),
            $request->query('per_page'),
            10
        );

        $query = User::query()
            ->with('subdivision')
            ->orderBy('role')
            ->orderBy('full_name');

        if ($filterView === 'deleted') {
            $query->onlyTrashed();
        } elseif ($filterView === 'all') {
            $query->withTrashed();
        }

        if ($filterQ !== '') {
            $query->where(function ($builder) use ($filterQ) {
                $builder->where('full_name', 'like', "%{$filterQ}%")
                    ->orWhere('email', 'like', "%{$filterQ}%");
            });
        }

        if (in_array($filterRole, ['admin', 'security', 'staff'], true)) {
            $query->where('role', $filterRole);
        }

        if ($filterSubdivision !== null && $filterSubdivision !== '') {
            if ($filterSubdivision === 'none') {
                $query->whereNull('subdivision_id');
            } elseif (ctype_digit((string) $filterSubdivision)) {
                $query->where('subdivision_id', (int) $filterSubdivision);
            }
        }

        $users = $query
            ->paginate($perPage)
            ->withQueryString();
        $subdivisions = Subdivision::orderBy('subdivision_name')->get();
        return view('users.index', compact(
            'users',
            'subdivisions',
            'filterQ',
            'filterRole',
            'filterSubdivision',
            'filterView',
            'perPage'
        ));
    }

    public function show(Request $request, User $user): View
    {
        $user->load(['subdivision', 'resident.house']);

        return view('users.show', [
            'managedUser' => $user,
            'indexContext' => $this->indexContext($request),
        ]);
    }

    public function store(Request $request)
    {
        $isResident = $request->input('role') === 'resident';

        $rules = [
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'extension' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'security', 'staff', 'resident'])],
            'is_active' => ['nullable', 'boolean'],
            'subdivision_id' => ['nullable', 'integer', 'exists:subdivisions,subdivision_id'],
        ];

        if ($isResident) {
            $rules['resident_mode'] = ['nullable', Rule::in(['existing', 'new'])];
            $rules['resident_id'] = ['nullable', 'integer', 'exists:residents,resident_id'];
            $rules['new_resident_subdivision_id'] = ['nullable', 'integer', 'exists:subdivisions,subdivision_id'];
            $rules['new_resident_house_id'] = ['nullable', 'integer', 'exists:houses,house_id'];
            $rules['new_resident_phone'] = ['nullable', 'string', 'max:40'];
        }

        $data = $request->validate($rules);

        if ($isResident) {
            return $this->storeResidentAccount($request, $data);
        }

        if ($data['role'] !== 'admin' && empty($data['subdivision_id'])) {
            return back()->withErrors(['subdivision_id' => 'Please select a subdivision for non-admin users.'])->withInput();
        }

        if ($data['role'] === 'admin') {
            $data['subdivision_id'] = null;
        }
        $data['resident_id'] = null;
        $data['is_active'] = $request->boolean('is_active', true);

        $plainPassword = $this->generateTemporaryPassword();
        $data['password'] = $plainPassword;
        $data['requires_password_change'] = true;

        User::create($data);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.')
            ->with('generated_password', $plainPassword);
    }

    private function storeResidentAccount(Request $request, array $data): RedirectResponse
    {
        $mode = $request->input('resident_mode', 'existing');

        if ($mode === 'new') {
            $subdivisionId = (int) $request->input('new_resident_subdivision_id');
            if (!$subdivisionId) {
                return back()->withErrors(['new_resident_subdivision_id' => 'Please select a subdivision for the new resident.'])->withInput();
            }

            $house = House::query()
                ->where('house_id', (int) $request->input('new_resident_house_id'))
                ->where('subdivision_id', $subdivisionId)
                ->first();
            if (!$house) {
                return back()->withErrors(['new_resident_house_id' => 'Please select a valid house for the chosen subdivision.'])->withInput();
            }

            $resident = Resident::create([
                'subdivision_id' => $subdivisionId,
                'house_id' => $house->house_id,
                'full_name' => User::formatFullName($data['first_name'], $data['surname'], $data['middle_name'] ?? null, $data['extension'] ?? null),
                'phone' => $request->input('new_resident_phone'),
                'email' => $data['email'],
                'address_or_unit' => $house->display_address,
                'status' => 'Active',
            ]);

            $residentId = $resident->resident_id;
            $userSubdivisionId = $subdivisionId;
        } else {
            $residentId = (int) ($data['resident_id'] ?? 0);
            if (!$residentId) {
                return back()->withErrors(['resident_id' => 'Please select a resident record to link to this account.'])->withInput();
            }

            $resident = Resident::find($residentId);
            if (!$resident) {
                return back()->withErrors(['resident_id' => 'The selected resident record was not found.'])->withInput();
            }

            $alreadyLinked = User::query()
                ->where('resident_id', $residentId)
                ->whereNull('deleted_at')
                ->exists();
            if ($alreadyLinked) {
                return back()->withErrors(['resident_id' => 'This resident is already linked to an active account.'])->withInput();
            }

            $userSubdivisionId = $resident->subdivision_id;
        }

        $plainPassword = $this->generateTemporaryPassword();

        User::create([
            'surname' => $data['surname'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'extension' => $data['extension'] ?? null,
            'email' => $data['email'],
            'role' => 'resident',
            'password' => $plainPassword,
            'requires_password_change' => true,
            'subdivision_id' => $userSubdivisionId,
            'resident_id' => $residentId,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.')
            ->with('generated_password', $plainPassword);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(
            [
                'surname' => ['required', 'string', 'max:100'],
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'extension' => ['nullable', 'string', 'max:20'],
                'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
                'role' => ['required', Rule::in(['admin', 'security', 'staff'])],
                'is_active' => ['nullable', 'boolean'],
                'subdivision_id' => ['nullable', 'integer', 'exists:subdivisions,subdivision_id'],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]
        );

        if ($user->role === 'admin' && $data['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Cannot remove the last administrator.'])->withInput();
        }

        if ($data['role'] !== 'admin' && empty($data['subdivision_id'])) {
            return back()->withErrors(['subdivision_id' => 'Please select a subdivision for non-admin users.'])->withInput();
        }

        if ($data['role'] === 'admin') {
            $data['subdivision_id'] = null;
            $data['resident_id'] = null;
        } else {
            $data['resident_id'] = null;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            // An admin-set password supersedes any pending temporary password.
            $data['temporary_password'] = null;
        }

        $data['is_active'] = $request->boolean('is_active');

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->user_id === $user->user_id) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()->route('users.index')->with('error', 'You cannot delete the last administrator.');
        }

        $user->delete();

        return redirect()->route('users.index', $this->indexContext($request))
            ->with('success', 'User archived successfully.');
    }

    public function restore(Request $request, int $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);

        if (!$user->trashed()) {
            return redirect()->route('users.index', $this->indexContext($request))
                ->with('error', 'That user is already active.');
        }

        if ($user->resident_id && User::query()
            ->where('resident_id', $user->resident_id)
            ->whereNull('deleted_at')
            ->exists()) {
            return redirect()->route('users.index', $this->indexContext($request))
                ->with('error', 'This resident is already linked to another active user account.');
        }

        $user->restore();

        return redirect()->route('users.index', $this->indexContext($request))
            ->with('success', 'User restored successfully.');
    }

    public function forceDelete(Request $request, int $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);

        if (!$user->trashed()) {
            return redirect()->route('users.index', $this->indexContext($request))
                ->with('error', 'Only archived users can be permanently deleted.');
        }

        $user->forceDelete();

        return redirect()->route('users.index', $this->indexContext($request))
            ->with('success', 'User permanently deleted.');
    }

    private function indexContext(Request $request): array
    {
        $context = array_filter([
            'q' => $request->input('q', $request->query('q')),
            'role' => $request->input('role', $request->query('role')),
            'subdivision_id' => $request->input('subdivision_id', $request->query('subdivision_id')),
            'per_page' => $this->resolvePerPageChoice(
                $request->input('per_page_custom', $request->query('per_page_custom')),
                $request->input('per_page', $request->query('per_page')),
                10
            ),
        ], static fn ($value) => $value !== null && $value !== '');

        $view = $request->input('view', $request->query('view', 'active'));
        if ($view !== 'active') {
            $context['view'] = $view;
        }

        return $context;
    }

    private function generateTemporaryPassword(): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 12);
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
