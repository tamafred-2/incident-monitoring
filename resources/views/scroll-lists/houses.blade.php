@forelse ($houses as $house)
    @php $ownerResident = $house->residents->firstWhere('relation_to_owner', 'Owner'); @endphp
    <tr data-record-key="{{ $house->getKey() }}" data-row
        data-search="{{ \Illuminate\Support\Str::lower(trim($house->display_address.' '.$house->street.' '.$house->block.' '.$house->lot.' '.($house->subdivision?->subdivision_name).' '.($ownerResident?->full_name))) }}"
        class="transition hover:bg-slate-50/60">
        <td class="px-6 py-4">
            <div class="font-medium text-slate-900">{{ $house->display_address }}</div>
            <div class="mt-0.5 text-xs text-slate-400">{{ $house->subdivision?->subdivision_name ?? '-' }}</div>
        </td>
        <td class="px-6 py-4 text-slate-600">{{ $house->street ?: '-' }}</td>
        <td class="px-6 py-4 text-slate-600">
            <div class="max-w-[14rem] truncate">{{ $ownerResident?->full_name ?: '—' }}</div>
        </td>
        <td class="px-6 py-4 text-slate-600">{{ $house->residents->count() }}</td>
        <td class="px-6 py-4">
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('houses.show', array_merge(['house' => $house], array_filter(['q' => $filterQ, 'subdivision_id' => $filterSubdivision], static fn ($v) => $v !== null && $v !== '' && $v !== 0 && $v !== '0'))) }}"
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                >View</a>
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'edit-house-{{ $house->house_id }}')"
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                >Edit</button>
                @if ($house->canDelete())
<button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'delete-house-{{ $house->house_id }}')"
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-rose-500 hover:bg-rose-50"
                >Delete</button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-400">No house records found.</td>
    </tr>
@endforelse
<tr data-empty hidden>
    <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-400">No results match your search.</td>
</tr>
