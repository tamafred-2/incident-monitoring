@foreach ($houses as $house)
<div data-record-modal="{{ $house->getKey() }}">
                <x-modal name="edit-house-{{ $house->house_id }}" :show="$errors->houseEdit->any() && (string) old('edit_house_id') === (string) $house->house_id" maxWidth="2xl" focusable>
                    <div class="bg-white p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Edit House</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $house->display_address }}</p>
                            </div>
                            <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-600" aria-label="Close">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 01-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('houses.update', $house) }}" class="mt-5 space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="house_form_mode" value="edit">
                            <input type="hidden" name="edit_house_id" value="{{ $house->house_id }}">
                            @include('houses.partials.form-fields', ['house' => $house, 'errorBag' => 'houseEdit'])
                            <div class="flex gap-2 pt-1">
                                <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Save</button>
                                <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                            </div>
                        </form>
                    </div>
                </x-modal>

                <x-modal name="delete-house-{{ $house->house_id }}" maxWidth="sm" focusable>
                    <div class="bg-white p-6 sm:p-8">
                        <h3 class="text-base font-semibold text-slate-900">Delete {{ $house->display_address }}?</h3>
                        <p class="mt-2 text-sm text-slate-500">This will remove the house from the registry. This cannot be undone.</p>
                        <form method="POST" action="{{ route('houses.destroy', $house) }}" class="mt-5 flex justify-end gap-2">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="q" value="{{ $filterQ }}">
                            <input type="hidden" name="subdivision_id" value="{{ $filterSubdivision }}">
                            <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                            <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Delete</button>
                        </form>
                    </div>
                </x-modal>
            </div>
@endforeach
