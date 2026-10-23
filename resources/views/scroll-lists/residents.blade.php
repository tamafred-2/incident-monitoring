@forelse ($residents as $resident)
    <tr data-record-key="{{ $resident->getKey() }}">
        <td class="px-6 py-4">
            <div class="min-w-[12rem]">
                <div class="font-medium text-slate-900">{{ $resident->full_name }}</div>
                @if ($resident->is_deceased)<span class="text-xs font-medium text-slate-500">Deceased</span>@endif
                <div class="mt-2">
                    @include('residents.partials.phone-link')
                </div>
            </div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            <div class="max-w-[12rem] truncate" title="{{ $resident->house?->display_address ?: '-' }}">
                {{ $resident->house?->display_address ?: '-' }}
            </div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            <div class="max-w-[14rem] truncate" title="{{ $resident->house?->street ?: ($resident->address_or_unit ?: '-') }}">
                {{ $resident->house?->street ?: ($resident->address_or_unit ?: '-') }}
            </div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            <div class="max-w-[10rem] truncate" title="{{ $resident->relation_to_owner ?: '-' }}">
                {{ $resident->relation_to_owner ?: '-' }}
            </div>
        </td>
        @if (auth()->user()->isAdmin())
            <td class="px-6 py-4 text-slate-600">
                <x-status-badge :status="$resident->status" class="whitespace-nowrap" />
            </td>
        @endif
        <td class="px-6 py-4">
            <div class="flex items-center gap-3 flex-nowrap">
                <a
                    href="{{ route('residents.show', array_merge(['resident' => $resident], array_filter(['q' => $filterQ, 'status' => $filterStatus, 'subdivision_id' => $filterSubdivision, 'per_page' => $perPage], static fn ($value) => $value !== null && $value !== '' && $value !== 0))) }}"
                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                >
                    View
                </a>
                @if (auth()->user()->isAdmin())
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'edit-resident-{{ $resident->resident_id }}')"
                        class="inline-flex items-center rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:border-brand-300 hover:bg-brand-100 hover:text-brand-800"
                    >
                        Edit
                    </button>
                @endif

            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}" class="px-6 py-10 text-center text-slate-500">No residents found.</td>
    </tr>
@endforelse
