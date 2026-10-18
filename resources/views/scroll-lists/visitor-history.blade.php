@forelse ($visitors as $visitor)
    <tr data-record-key="{{ $visitor->getKey() }}">
        <td class="px-6 py-4">
            <div class="min-w-[12rem]">
                <div class="font-medium text-slate-900">{{ $visitor->full_name }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ $visitor->phone ?: 'No phone provided' }}</div>
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
        <td class="px-6 py-4 text-slate-600">
            @if ($visitor->check_out)
                <div class="min-w-[9rem]">
                    <div class="font-medium whitespace-nowrap text-slate-700">{{ $visitor->check_out->format('M j, Y') }}</div>
                    <div class="mt-1 text-xs whitespace-nowrap text-slate-500">{{ $visitor->check_out->format('h:i A') }}</div>
                </div>
            @else
                -
            @endif
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-slate-600">
            @if ($visitor->check_in && !$visitor->check_out && $visitor->status === \App\Enums\VisitorStatus::Inside)
                <span class="text-xs text-slate-500">Still inside</span>
            @else
                {{ $visitor->visit_duration_label }}
            @endif
        </td>
        <td class="px-6 py-4 text-slate-600">
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('visitors.show', ['visitor' => $visitor->visitor_id]) }}"
                    class="px-3 py-2 text-xs font-semibold border rounded-lg border-slate-300 text-slate-700 hover:bg-slate-50"
                >
                    View
                </a>
                @if (auth()->user()->isAdmin())
                    <a
                        href="{{ route('visitors.edit', array_merge(['visitor' => $visitor->visitor_id], request()->only(['tab', 'q', 'subdivision_id', 'history_per_page', 'check_out_per_page']))) }}"
                        class="px-3 py-2 text-xs font-semibold border rounded-lg border-brand-300 text-brand-700 hover:bg-brand-50"
                    >
                        Edit
                    </a>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-6 py-10 text-center text-slate-500">No visitors found.</td>
    </tr>
@endforelse
