<?php

namespace App\Http\Controllers;

use App\Enums\ActiveStatus;
use App\Enums\VisitorStatus;
use App\Models\Subdivision;
use App\Models\Visitor;
use App\Models\VisitorProfile;
use App\Rules\PersonName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VisitorProfileController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $profiles = $this->query($request)
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($query) use ($q): void {
                    $query->where('full_name', 'like', '%'.$q.'%')->orWhere('phone', 'like', '%'.$q.'%');
                    if (preg_match('/^(?:V-)?0*(\d+)$/i', $q, $match)) {
                        $query->orWhere('profile_id', (int) $match[1]);
                    }
                });
            })
            ->withCount(['visits' => fn ($query) => $query->withTrashed()])->withExists(['visits as is_inside' => fn ($query) => $query->where('status', VisitorStatus::Inside)])
            ->orderBy('full_name')->orderBy('profile_id')->simplePaginate(10)->withQueryString();

        if ($request->wantsJson() && !$request->hasHeader('X-Scroll-List')) {
            return response()->json(['profiles' => $profiles->map(fn ($profile) => $this->format($profile)), 'has_more' => $profiles->hasMorePages()]);
        }

        return $this->scrollView('visitor-profiles.index', compact('profiles', 'q'), ['visitor-profiles' => 'profiles']);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $subdivisionId = $request->user()->isAdmin() ? $request->integer('subdivision_id') : $request->user()->allowedSubdivisionId();
        if ($request->user()->isAdmin() && !$subdivisionId) {
            $activeIds = Subdivision::where('status', ActiveStatus::Active)->limit(2)->pluck('subdivision_id');
            $subdivisionId = $activeIds->count() === 1 ? $activeIds->first() : null;
        }
        abort_unless(Subdivision::whereKey($subdivisionId)->exists(), 422, 'Please select a valid subdivision.');
        $this->ensureUniqueName($data, $subdivisionId);
        $data['id_photo_path'] = $request->file('id_photo')->store('uploads/visitors/id-photos', 'public');
        unset($data['id_photo']);
        $profile = VisitorProfile::create($data + ['subdivision_id' => $subdivisionId]);

        return redirect()->route('visitor-profiles.show', $profile)->with('success', 'Visitor profile created. You can now add a visit.');
    }

    public function show(Request $request, VisitorProfile $profile)
    {
        $this->authorizeProfile($request, $profile);
        $visits = $profile->visits()->withTrashed()->orderByDesc('check_in')->orderByDesc('visitor_id')->simplePaginate(10);
        $isInside = $profile->visits()->where('status', VisitorStatus::Inside)->exists();

        return view('visitor-profiles.show', compact('profile', 'visits', 'isInside'));
    }

    public function update(Request $request, VisitorProfile $profile)
    {
        $this->authorizeProfile($request, $profile);
        $data = $this->validated($request, true);
        $this->ensureUniqueName($data, $profile->subdivision_id, $profile);
        if ($request->hasFile('id_photo')) {
            $data['id_photo_path'] = $request->file('id_photo')->store('uploads/visitors/id-photos', 'public');
        }
        unset($data['id_photo']);
        $profile->update($data);

        return redirect()->route('visitor-profiles.show', $profile)->with('success', 'Visitor profile updated. Previous visits retain their recorded details.');
    }

    public function photo(Request $request, VisitorProfile $profile)
    {
        $this->authorizeProfile($request, $profile);
        abort_unless($profile->id_photo_path && Storage::disk('public')->exists($profile->id_photo_path), 404);

        return response()->file(Storage::disk('public')->path($profile->id_photo_path));
    }

    private function query(Request $request)
    {
        return VisitorProfile::query()->when(! $request->user()->isAdmin(), fn ($query) => $query->where('subdivision_id', $request->user()->allowedSubdivisionId()))
            ->when($request->user()->isAdmin() && $request->integer('subdivision_id'), fn ($query) => $query->where('subdivision_id', $request->integer('subdivision_id')));
    }

    private function authorizeProfile(Request $request, VisitorProfile $profile): void
    {
        abort_unless($request->user()->canAccessSubdivision($profile->subdivision_id), 403);
    }

    private function ensureUniqueName(array $data, int $subdivisionId, ?VisitorProfile $profile = null): void
    {
        $name = Visitor::formatFullName($data['first_name'], $data['middle_initials'] ?? null, $data['surname'], $data['extension'] ?? null);
        $exists = VisitorProfile::where('subdivision_id', $subdivisionId)
            ->whereRaw('LOWER(TRIM(full_name)) = ?', [mb_strtolower($name)])
            ->when($profile, fn ($query) => $query->where('profile_id', '!=', $profile->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'surname' => 'A visitor with this exact full name already exists. Open their profile to add a visit. If this is a different person, include their middle name or extension to distinguish them.',
            ]);
        }
    }

    private function validated(Request $request, bool $editing = false): array
    {
        $data = $request->validate([
            'surname' => ['required', 'string', 'max:50', new PersonName],
            'first_name' => ['required', 'string', 'max:50', new PersonName],
            'middle_initials' => ['nullable', 'string', 'max:20', new PersonName],
            'extension' => ['nullable', 'string', 'max:20', new PersonName],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'id_photo' => [$editing ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);
        foreach (['first_name', 'middle_initials', 'surname', 'extension'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = preg_replace('/\s+/u', ' ', trim($data[$field]));
            }
        }
        $name = Visitor::formatFullName($data['first_name'], $data['middle_initials'] ?? null, $data['surname'], $data['extension'] ?? null);
        if (mb_strlen($name) > 100) {
            throw ValidationException::withMessages(['surname' => 'The complete visitor name must not exceed 100 characters.']);
        }

        return $data;
    }

    public function format(VisitorProfile $profile): array
    {
        return ['id' => $profile->getKey(), 'number' => $profile->visitor_number, 'name' => $profile->full_name, 'phone' => $profile->phone, 'has_photo' => (bool) $profile->id_photo_path, 'is_inside' => (bool) $profile->is_inside];
    }
}
