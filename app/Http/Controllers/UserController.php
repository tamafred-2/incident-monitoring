<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Subdivision;
use App\Models\User;
use App\Rules\PersonName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filterQ = trim((string) $request->query('q', ''));
        $filterRole = trim((string) $request->query('role', ''));
        $filterSubdivision = $request->query('subdivision_id');
        $filterView = trim((string) $request->query('view', 'all'));
        $perPage = 10;

        $query = User::query()
            ->whereIn('role', UserRole::manageableValues())
            ->with('subdivision')
            ->orderBy('role')
            ->orderBy('full_name');

        if (in_array($filterView, ['active', 'inactive'], true)) {
            $query->where('is_active', $filterView === 'active');
        } elseif ($filterView === 'deleted') {
            $query->onlyTrashed();

        }

        if ($filterQ !== '') {
            $query->where(function ($builder) use ($filterQ) {
                $builder->where('full_name', 'like', "%{$filterQ}%")
                    ->orWhere('email', 'like', "%{$filterQ}%");
            });
        }

        if (in_array($filterRole, UserRole::manageableValues(), true)) {
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
            ->orderBy('user_id')->simplePaginate($perPage)
            ->withQueryString();
        $subdivisions = Subdivision::orderBy('subdivision_name')->get();

        return $this->scrollView('users.index', compact(
            'users',
            'subdivisions',
            'filterQ',
            'filterRole',
            'filterSubdivision',
            'filterView',
            'perPage'
        ), ['users' => 'users']);
    }

    public function show(Request $request, User $user): View
    {
        $user->load('subdivision');

        return view('users.show', [
            'managedUser' => $user,
            'indexContext' => $this->indexContext($request),
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'surname' => ['required', 'string', 'max:100', new PersonName],
            'first_name' => ['required', 'string', 'max:100', new PersonName],
            'middle_name' => ['nullable', 'string', 'max:100', new PersonName],
            'extension' => ['nullable', 'string', 'max:20', new PersonName],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['nullable', 'boolean'],
            'subdivision_id' => ['nullable', 'integer', 'exists:subdivisions,subdivision_id'],
        ];

        $data = $request->validate($rules);

        if ($data['role'] !== UserRole::Admin->value && empty($data['subdivision_id'])) {
            return back()->withErrors(['subdivision_id' => 'Please select a subdivision for non-admin users.'])->withInput();
        }

        if ($data['role'] === UserRole::Admin->value) {
            $data['subdivision_id'] = null;
        }
        $data['is_active'] = $request->boolean('is_active', true);

        $plainPassword = $this->generateTemporaryPassword();
        $data['password'] = $plainPassword;
        $data['requires_password_change'] = true;

        User::create($data);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.')
            ->with('generated_password', $plainPassword);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(
            [
                'surname' => ['required', 'string', 'max:100', new PersonName],
                'first_name' => ['required', 'string', 'max:100', new PersonName],
                'middle_name' => ['nullable', 'string', 'max:100', new PersonName],
                'extension' => ['nullable', 'string', 'max:20', new PersonName],
                'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
                'role' => ['required', Rule::in(UserRole::manageableValues())],
                'is_active' => ['nullable', 'boolean'],
                'subdivision_id' => ['nullable', 'integer', 'exists:subdivisions,subdivision_id'],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]
        );

        if ($error = $this->accountChangeError($request, $user, $request->boolean('is_active', $user->is_active), $data['role'])) {
            return back()->withErrors(['is_active' => $error])->withInput();
        }

        if ($data['role'] !== UserRole::Admin->value && empty($data['subdivision_id'])) {
            return back()->withErrors(['subdivision_id' => 'Please select a subdivision for non-admin users.'])->withInput();
        }

        if ($data['role'] === UserRole::Admin->value) {
            $data['subdivision_id'] = null;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            // An admin-set password supersedes any pending temporary password.
            $data['temporary_password'] = null;
        }

        $data['is_active'] = $request->boolean('is_active', $user->is_active);

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function setStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $active = (bool) $data['is_active'];
        if ($error = $this->accountChangeError($request, $user, $active, $user->role->value)) {
            return redirect()->route('users.index', $this->indexContext($request))->with('error', $error);
        }

        $user->update(['is_active' => $active]);

        return redirect()->route('users.index', array_replace($this->indexContext($request), ['view' => 'all']))
            ->with('success', $active ? 'User activated successfully.' : 'User deactivated successfully.');
    }

    private function accountChangeError(Request $request, User $user, bool $active, string $role): ?string
    {
        if (! $active && $request->user()->is($user)) {
            return 'You cannot deactivate your own account.';
        }
        if ($user->role === UserRole::Admin && $user->is_active && (! $active || $role !== UserRole::Admin->value)
            && ! User::where('role', UserRole::Admin)->where('is_active', true)->where('user_id', '!=', $user->user_id)->exists()) {
            return 'You cannot deactivate or change the role of the last active administrator.';
        }

        return null;
    }

    public function restore(Request $request, int $userId)
    {
        $user = User::withTrashed()->whereIn('role', UserRole::manageableValues())->findOrFail($userId);

        if (! $user->trashed()) {
            return redirect()->route('users.index', $this->indexContext($request))
                ->with('error', 'That user is already active.');
        }

        $user->restore();

        return redirect()->route('users.index', $this->indexContext($request))
            ->with('success', 'User restored successfully.');
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

        $view = $request->input('view', $request->query('view', 'all'));
        if ($view !== 'all') {
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
