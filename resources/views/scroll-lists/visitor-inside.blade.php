@forelse ($insideVisitors as $visitor)
    <tr data-record-key="{{ $visitor->getKey() }}" data-row
        data-search="{{ \Illuminate\Support\Str::lower(trim($visitor->full_name.' '.$visitor->phone.' '.$visitor->purpose.' '.$visitor->host_employee.' '.$visitor->house_address_or_unit)) }}">
        <td class="px-6 py-4">
            <div class="min-w-[12rem]">
                <div class="font-medium text-slate-900">{{ $visitor->full_name }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ $visitor->phone ?: 'No phone provided' }}</div>
                @include('visitors.partials.time-limit')
            </div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            <div class="max-w-[14rem] truncate" title="{{ $visitor->host_employee ?: 'Walk-in' }}">
                {{ $visitor->host_employee ?: 'Walk-in' }}
            </div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            <div class="max-w-[12rem] truncate" title="{{ $visitor->house_address_or_unit ?: '-' }}">
                {{ $visitor->house_address_or_unit ?: '-' }}
            </div>
        </td>
        <td class="px-6 py-4 text-slate-600">
            @if ($visitor->check_in)
                <div class="min-w-[9rem]">
                    <div class="font-medium whitespace-nowrap text-slate-700">{{ $visitor->check_in->format('M j, Y') }}</div>
                    <div class="mt-1 text-xs whitespace-nowrap text-slate-500">{{ $visitor->check_in->format('h:i A') }}</div>
                </div>
            @else
                -
            @endif
        </td>
        <td class="px-6 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('visitors.show', ['visitor' => $visitor->visitor_id]) }}"
                    class="px-3 py-2 text-xs font-semibold border rounded-lg border-slate-300 text-slate-700 hover:bg-slate-50"
                >
                    View
                </a>
                @if (auth()->user()->hasRole('security'))
                    <form method="POST" action="{{ route('visitors.checkout', $visitor) }}">
                        @csrf
                        <input type="hidden" name="tab" value="check-out">
                        <input type="hidden" name="q" value="{{ $filterQ }}">
                        <input type="hidden" name="date_from" value="{{ $filterDateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $filterDateTo }}">
                        <input type="hidden" name="history_per_page" value="{{ $historyPerPage }}">
                        <input type="hidden" name="check_out_per_page" value="{{ $checkOutPerPage }}">
                        <button class="px-3 py-2 text-xs font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">Check Out</button>
                    </form>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-6 py-10 text-center text-slate-500">No visitors are currently inside.</td>
    </tr>
@endforelse
<tr data-empty hidden>
    <td colspan="5" class="px-6 py-10 text-center text-slate-500">No inside visitors match your search. Press Enter to search all.</td>
</tr>
