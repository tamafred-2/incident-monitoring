@foreach ($requests as $req)
    <tr data-record-key="{{ $req->getKey() }}">
        <td class="px-4 py-3">
            @if ($req->id_photo_path)
                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'id-photo-{{ $req->request_id }}')">
                        <img src="{{ route('resident.visitors.photo', $req->request_id) }}" alt="ID" class="object-cover w-10 h-10 rounded-lg border border-slate-200 hover:opacity-80">
                </button>
            @else
                <span class="text-xs text-slate-400">-</span>
            @endif
        </td>
        <td class="px-4 py-3 font-medium text-slate-900">{{ $req->visitor_name }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $req->phone ?: '-' }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $req->purpose ?: '-' }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $req->house_address_or_unit ?: '-' }}</td>
        <td class="px-4 py-3 text-slate-500">{{ $req->requested_at->format('M j, Y h:i A') }}</td>
        <td class="px-4 py-3">
            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                {{ $req->status === \App\Enums\VisitorRequestStatus::Approved ? 'bg-emerald-100 text-emerald-700' : ($req->status === \App\Enums\VisitorRequestStatus::Declined ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                {{ $req->status }}
            </span>
        </td>
        <td class="px-4 py-3">
            @if ($req->status === \App\Enums\VisitorRequestStatus::Pending)
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
                        Deny
                    </button>
                </div>
            @else
                <span class="text-xs text-slate-400">{{ $req->responded_at?->format('M j, Y') ?? '-' }}</span>
            @endif
        </td>
    </tr>
@endforeach

