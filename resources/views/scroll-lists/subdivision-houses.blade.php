@forelse ($houses as $house)
    <tr data-record-key="{{ $house->getKey() }}">
        <td class="px-6 py-4 font-medium text-slate-900">
            <div class="min-w-[6rem]">{{ $house->block }}</div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            <div class="min-w-[5rem]">{{ $house->lot }}</div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            <div class="max-w-[14rem] truncate" title="{{ $house->street ?: '-' }}">{{ $house->street ?: '-' }}</div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            <span class="inline-flex whitespace-nowrap rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                {{ $house->residents->count() }} resident{{ $house->residents->count() === 1 ? '' : 's' }}
            </span>
        </td>
        <td class="px-6 py-4">
            @php
                $houseViewContext = array_filter([
                    'house' => $house,
                    'from_subdivision' => 1,
                    'q' => $filterQ,
                    'per_page' => $perPage,
                    'page' => $houses->currentPage(),
                ], static fn ($value) => $value !== null && $value !== '');
            @endphp
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('houses.show', $houseViewContext) }}"
                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    View
                </a>
                @if (auth()->user()->isAdmin())
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'edit-house-{{ $house->house_id }}')"
                        class="inline-flex items-center rounded-xl border border-brand-200 bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100"
                    >
                        Edit
                    </button>
                    @if ($house->canDelete())
<button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'delete-house-{{ $house->house_id }}')"
                        class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                    >
                        Delete
                    </button>
                @endif
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-6 py-10 text-center text-slate-500">No house records found.</td>
    </tr>
@endforelse
