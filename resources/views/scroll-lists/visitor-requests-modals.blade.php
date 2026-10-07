@foreach ($requests as $req)
<div data-record-modal="{{ $req->getKey() }}">
        @if ($req->id_photo_path)
            <x-modal name="id-photo-{{ $req->request_id }}" maxWidth="md" focusable>
                <div class="p-6 bg-white text-center">
                    <h3 class="mb-4 text-lg font-semibold text-slate-900">{{ $req->visitor_name }} - ID Photo</h3>
                    <img src="{{ route('resident.visitors.photo', $req->request_id) }}" alt="ID Photo" class="max-w-full mx-auto rounded-xl border border-slate-200">
                </div>
            </x-modal>
        @endif
        @if ($req->status === \App\Enums\VisitorRequestStatus::Pending)
            <x-modal name="approve-visitor-{{ $req->request_id }}" maxWidth="md" focusable>
                <div class="p-6 bg-white sm:p-8">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-12 h-12 shrink-0 rounded-2xl bg-emerald-100 text-emerald-600">
                            <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Approve Visitor?</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                <span class="font-medium text-slate-900">{{ $req->visitor_name }}</span> will be approved and this response will be sent to Admin/Guard for entry confirmation.
                            </p>
                        </div>
                    </div>

                    @if ($req->id_photo_path)
                        <div class="mt-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">ID Photo</p>
                            <img src="{{ route('resident.visitors.photo', $req->request_id) }}" alt="ID Photo" class="max-w-full rounded-xl border border-slate-200">
                        </div>
                    @endif

                    <form method="POST" action="{{ route('resident.visitors.approve', $req->request_id) }}" class="flex flex-wrap justify-end gap-3 mt-6">
                        @csrf
                        <button
                            type="button"
                            x-on:click="$dispatch('close')"
                            class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                        <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-emerald-600 hover:bg-emerald-700">
                            Approve And Allow Entry
                        </button>
                    </form>
                </div>
            </x-modal>

            <x-modal name="decline-visitor-{{ $req->request_id }}" maxWidth="md" focusable>
                <div class="p-6 bg-white sm:p-8">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-12 h-12 shrink-0 rounded-2xl bg-rose-100 text-rose-600">
                            <svg class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5A.75.75 0 0010 7z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Deny Visitor?</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                <span class="font-medium text-slate-900">{{ $req->visitor_name }}</span> will be declined and should not be allowed entry.
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('resident.visitors.decline', $req->request_id) }}" class="flex flex-wrap justify-end gap-3 mt-6">
                        @csrf
                        <button
                            type="button"
                            x-on:click="$dispatch('close')"
                            class="px-4 py-2 text-sm font-semibold border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                        <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-rose-600 hover:bg-rose-700">
                            Deny Visitor
                        </button>
                    </form>
                </div>
            </x-modal>
        @endif
    </div>
@endforeach

