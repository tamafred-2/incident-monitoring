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


                @endif
            </div>
@endforeach
