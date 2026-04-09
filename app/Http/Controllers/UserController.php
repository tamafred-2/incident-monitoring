<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
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

        if (in_array($filterRole, ['admin', 'security', 'staff', 'investigator', 'resident'], true)) {
            $query->where('role', $filterRole);
        }

        if ($filterSubdivision !== null && $filterSubdivision !== '') {
            if ($filterSubdivision === 'none') {
                $query->whereNull('subdivision_id');
            } elseif (ctype_digit((string) $filterSubdivision)) {
                $query->where('subdivision_id', (int) $filterSubdivision);
            }
        }

        $users = $query->get();
        $subdivisions = Subdivision::orderBy('subdivision_name')->get();
        $residents = Resident::query()
            ->with(['subdivision', 'house'])
            ->where('status', 'Active')
            ->whereNotNull('house_id')
            ->orderBy('full_name')
            ->get();

        return view('users.index', compact(
            'users',
            'subdivisions',
            'residents',
            'filterQ',
            'filterRole',
            'filterSubdivision',
            'filterView'
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
        $data = $request->validate([
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'extension' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'security', 'staff', 'investigator', 'resident'])],
            'subdivision_id' => ['nullable', 'integer', 'exists:subdivisions,subdivision_id'],
            'resident_id' => ['nullable', 'integer', 'exists:residents,resident_id', Rule::unique('users', 'resident_id')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($data['role'] === 'resident') {
            $resident = Resident::query()->find($data['resident_id'] ?? null);

            if (!$resident) {
                return back()->withErrors(['resident_id' => 'Please select a resident record that is already assigned to a house.'])->withInput();
            }

            if ($resident->status !== 'Active') {
                return back()->withErrors(['resident_id' => 'Only active resident records can be linked to resident accounts.'])->withInput();
            }

            if (!$resident->house_id) {
                return back()->withErrors(['resident_id' => 'The selected resident must be assigned to a house before creating a resident account.'])->withInput();
            }

            $data['subdivision_id'] = $resident->house?->subdivision_id ?? $resident->subdivision_id;
        } elseif ($data['role'] !== 'admin' && empty($data['subdivision_id'])) {
            return back()->withErrors(['subdivision_id' => 'Please select a subdivision for non-admin users.'])->withInput();
        }

        if ($data['role'] === 'admin') {
            $data['subdivision_id'] = null;
            $data['resident_id'] = null;
        } elseif ($data['role'] !== 'resident') {
            $data['resident_id'] = null;
        }

        User::create($data);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'extension' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'role' => ['required', Rule::in(['admin', 'security', 'staff', 'investigator', 'resident'])],
            'subdivision_id' => ['nullable', 'integer', 'exists:subdivisions,subdivision_id'],
            'resident_id' => ['nullable', 'integer', 'exists:residents,resident_id', Rule::unique('users', 'resident_id')->ignore($user->user_id, 'user_id')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($user->role === 'admin' && $data['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Cannot remove the last administrator.'])->withInput();
        }

        if ($data['role'] === 'resident') {
            $resident = Resident::query()->find($data['resident_id'] ?? null);

            if (!$resident) {
                return back()->withErrors(['resident_id' => 'Please select a resident record that is already assigned to a house.'])->withInput();
            }

            if ($resident->status !== 'Active') {
                return back()->withErrors(['resident_id' => 'Only active resident records can be linked to resident accounts.'])->withInput();
            }

            if (!$resident->house_id) {
                return back()->withErrors(['resident_id' => 'The selected resident must be assigned to a house before creating a resident account.'])->withInput();
            }

            $data['subdivision_id'] = $resident->house?->subdivision_id ?? $resident->subdivision_id;
        } elseif ($data['role'] !== 'admin' && empty($data['subdivision_id'])) {
            return back()->withErrors(['subdivision_id' => 'Please select a subdivision for non-admin users.'])->withInput();
        }

        if ($data['role'] === 'admin') {
            $data['subdivision_id'] = null;
            $data['resident_id'] = null;
        } elseif ($data['role'] !== 'resident') {
            $data['resident_id'] = null;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

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
        ], static fn ($value) => $value !== null && $value !== '');

        $view = $request->input('view', $request->query('view', 'active'));
        if ($view !== 'active') {
            $context['view'] = $view;
        }

        return $context;
    }
}
