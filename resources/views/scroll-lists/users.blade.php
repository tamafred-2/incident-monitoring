@forelse ($users as $user)
    <tr data-record-key="{{ $user->getKey() }}">
        <td class="px-6 py-4">
            <div class="min-w-[12rem]">
                <div class="font-medium text-slate-900">{{ $user->full_name }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ $user->subdivision?->subdivision_name ?? ($user->isAdmin() ? 'All subdivisions' : 'Unassigned subdivision') }}</div>
            </div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            <div class="max-w-[16rem] truncate" title="{{ $user->email }}">{{ $user->email }}</div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            <span class="inline-flex whitespace-nowrap rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $user->role?->label() }}</span>
        </td>
        <td class="px-6 py-4">
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $user->is_active ? 'Active' : 'Inactive' }}
            </span>
        </td>
        @if ($filterView === 'deleted')
            <td class="px-6 py-4 text-slate-600">
                @if ($user->deleted_at)
                    <div class="min-w-[9rem]">
                        <div class="whitespace-nowrap font-medium text-slate-700">{{ $user->deleted_at->format('M j, Y') }}</div>
                        <div class="mt-1 whitespace-nowrap text-xs text-slate-500">{{ $user->deleted_at->format('h:i A') }}</div>
                    </div>
                @else
                    -
                @endif
            </td>
        @endif
        <td class="px-6 py-4">
            <div class="flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('users.show', array_merge(['user' => $user], array_filter(['q' => $filterQ, 'role' => $filterRole, 'subdivision_id' => $filterSubdivision, 'view' => $filterView !== 'all' ? $filterView : null, 'per_page' => $perPage], static fn ($value) => $value !== null && $value !== ''))) }}"
                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                >
                    View
                </a>
                @if (!$user->trashed())
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'edit-user-{{ $user->user_id }}')"
                        class="inline-flex items-center rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:border-brand-300 hover:bg-brand-100 hover:text-brand-800"
                    >
                        Edit
                    </button>
                    @if (auth()->id() !== $user->user_id)
                        <form method="POST" action="{{ route('users.status', $user) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                            <input type="hidden" name="q" value="{{ $filterQ }}">
                            <input type="hidden" name="role" value="{{ $filterRole }}">
                            <input type="hidden" name="view" value="{{ $filterView }}">
                            <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                            <button type="submit" class="inline-flex items-center rounded-xl border px-3 py-1.5 text-sm font-semibold transition {{ $user->is_active ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    @endif
                @else
                    <form method="POST" action="{{ route('users.restore', $user->user_id) }}">
                        @csrf
                        <input type="hidden" name="q" value="{{ $filterQ }}">
                        <input type="hidden" name="role" value="{{ $filterRole }}">
                        <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                        <input type="hidden" name="view" value="{{ $filterView }}">
                        <input type="hidden" name="per_page" value="{{ $perPage }}">
                        <button type="submit" class="inline-flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Restore</button>
                    </form>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ $filterView === 'deleted' ? 6 : 5 }}" class="px-6 py-10 text-center text-slate-500">No users found.</td>
    </tr>
@endforelse
