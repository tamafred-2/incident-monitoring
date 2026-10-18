@foreach ($residents as $resident)
<div data-record-modal="{{ $resident->getKey() }}">
                @if (auth()->user()->isAdmin())
                    <x-modal name="edit-resident-{{ $resident->resident_id }}" :show="(string) old('edit_resident_id') === (string) $resident->resident_id" maxWidth="3xl" focusable>
                        <div
                            class="p-6 bg-white sm:p-8"
                            x-data
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Edit Resident</h3>
                                    <p class="mt-1 text-sm text-slate-500">Update {{ $resident->full_name }} and the assigned housing record.</p>
                                </div>
                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="p-2 transition border rounded-xl border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700"
                                    aria-label="Close edit resident modal"
                                >
                                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <form method="POST" action="{{ route('residents.update', $resident) }}" class="mt-6 space-y-4">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="edit_resident_id" value="{{ $resident->resident_id }}">

                                @include('residents.partials.form-fields', ['resident' => $resident, 'subdivisions' => $subdivisions, 'houses' => $houses])

                                <div class="flex flex-wrap gap-3 pt-2">
                                    <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-slate-900 hover:bg-slate-800">
                                        Save Changes
                                    </button>
                                    <button
                                        type="button"
                                        x-on:click="$dispatch('close')"
                                        class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </x-modal>

                    <x-modal name="delete-resident-{{ $resident->resident_id }}" maxWidth="md" focusable>
                        <div class="p-6 bg-white sm:p-8">
                            <div class="flex items-start gap-4">
                                <div class="flex items-center justify-center w-12 h-12 shrink-0 rounded-2xl bg-rose-100 text-rose-600">
                                    <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Delete Resident?</h3>
                                    <p class="mt-2 text-sm text-slate-600">
                                        {{ $resident->full_name }} will be removed from resident monitoring.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('residents.destroy', $resident) }}" class="flex flex-wrap justify-end gap-3 mt-6">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="q" value="{{ $filterQ }}">
                                <input type="hidden" name="status" value="{{ $filterStatus }}">
                                <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                                <input type="hidden" name="per_page" value="{{ $perPage }}">

                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                                <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-rose-600 hover:bg-rose-700">
                                    Delete Resident
                                </button>
                            </form>
                        </div>
                    </x-modal>
                @endif
            </div>
@endforeach
