<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">My Visitors</h2>
            <p class="mt-1 text-sm text-slate-500">Approve or reject visitors requesting to visit your home. Approved visitors are automatically checked in at the gate.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="flex flex-col max-w-5xl gap-6 px-4 mx-auto sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="pb-5 border-b border-slate-200">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">My Visitors</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Visitor Requests</h3>
                </div>

                @if ($requests->isEmpty())
                    <p class="mt-6 text-sm text-slate-500">No visitor requests yet.</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">ID Photo</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Visitor</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Phone</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Purpose</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Requested</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Status</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @foreach ($requests as $req)
                                    <tr>
                                        <td class="px-4 py-3">
                                            @if ($req->id_photo_path)
                                                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'id-photo-{{ $req->request_id }}')">
                                                        <img src="{{ route('resident.visitors.photo', $req->request_id) }}" alt="ID" class="object-cover w-10 h-10 rounded-lg border border-slate-200 hover:opacity-80">
                                                </button>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ $req->visitor_name }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $req->phone ?: '-' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $req->purpose ?: '-' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $req->house_address_or_unit ?: '-' }}</td>
                                        <td class="px-4 py-3 text-slate-500">{{ $req->requested_at->format('M j, Y h:i A') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                                {{ $req->status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($req->status === 'Declined' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                                {{ $req->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($req->status === 'Pending')
                                                <div class="flex gap-2">
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="$dispatch('open-modal', 'approve-visitor-{{ $req->request_id }}')"
                                                        class="px-3 py-2 text-xs font-semibold border rounded-lg border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        type="button"
                                                        x-data
                                                        x-on:click="$dispatch('open-modal', 'decline-visitor-{{ $req->request_id }}')"
                                                        class="px-3 py-2 text-xs font-semibold border rounded-lg border-rose-200 text-rose-700 hover:bg-rose-50"
                                                    >
                                                        Reject
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400">{{ $req->responded_at?->format('M j, Y') ?? '-' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @foreach ($requests as $req)
        @if ($req->id_photo_path)
            <x-modal name="id-photo-{{ $req->request_id }}" maxWidth="md" focusable>
                <div class="p-6 bg-white text-center">
                    <h3 class="mb-4 text-lg font-semibold text-slate-900">{{ $req->visitor_name }} — ID Photo</h3>
                    <img src="{{ route('resident.visitors.photo', $req->request_id) }}" alt="ID Photo" class="max-w-full mx-auto rounded-xl border border-slate-200">
                </div>
            </x-modal>
        @endif
        @if ($req->status === 'Pending')
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
                                <span class="font-medium text-slate-900">{{ $req->visitor_name }}</span> will be approved and checked in immediately.
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
                            Approve &amp; Check In
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
                            <h3 class="text-lg font-semibold text-slate-900">Reject Visitor?</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                <span class="font-medium text-slate-900">{{ $req->visitor_name }}</span> will be declined and will not be allowed entry.
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
                            Reject Visitor
                        </button>
                    </form>
                </div>
            </x-modal>
        @endif
    @endforeach
</x-app-layout>
