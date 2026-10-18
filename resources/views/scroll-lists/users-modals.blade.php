@foreach ($users as $user)
<div data-record-modal="{{ $user->getKey() }}">
    @if (!$user->trashed() && auth()->id() !== $user->user_id)
        <x-modal name="confirm-user-status-{{ $user->user_id }}" maxWidth="md" focusable>
            <div class="p-6" role="alertdialog" aria-modal="true" aria-labelledby="user-status-title-{{ $user->user_id }}" aria-describedby="user-status-description-{{ $user->user_id }}">
                <h3 id="user-status-title-{{ $user->user_id }}" class="text-lg font-semibold text-slate-900">
                    {{ $user->is_active ? 'Deactivate' : 'Activate' }} user?
                </h3>
                <p id="user-status-description-{{ $user->user_id }}" class="mt-3 text-sm text-slate-600">
                    @if ($user->is_active)
                        Are you sure you want to deactivate <strong>{{ $user->full_name }}</strong>? They will no longer be able to access their account until it is activated again.
                    @else
                        Are you sure you want to activate <strong>{{ $user->full_name }}</strong>? They will be able to sign in and access their account.
                    @endif
                </p>
                <form method="POST" action="{{ route('users.status', $user) }}" class="mt-6 flex justify-end gap-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                    <input type="hidden" name="q" value="{{ $filterQ }}">
                    <input type="hidden" name="role" value="{{ $filterRole }}">
                    <input type="hidden" name="view" value="{{ $filterView }}">
                    <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                    <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-xl px-4 py-2 text-sm font-semibold text-white {{ $user->is_active ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                        {{ $user->is_active ? 'Deactivate User' : 'Activate User' }}
                    </button>
                </form>
            </div>
        </x-modal>
    @endif

                <x-modal name="edit-user-{{ $user->user_id }}" :show="(string) old('edit_user_id') === (string) $user->user_id" maxWidth="2xl" focusable>
                    <div class="bg-white p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Edit User</h3>
                                <p class="mt-1 text-sm text-slate-500">Update account details for {{ $user->full_name }}.</p>
                            </div>
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                                aria-label="Close edit user modal"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('users.update', $user) }}" class="mt-6 space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="edit_user_id" value="{{ $user->user_id }}">

                            @include('users.partials.form-fields', [
                                'user' => $user,
                                'showPasswordFields' => true,
                            ])

                            <div class="flex flex-wrap gap-3 pt-2">
                                <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                    Save Changes
                                </button>
                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </x-modal>

            </div>
@endforeach
