@forelse ($records as $visitor)
    <tr class="h-14 min-[1600px]:h-16 transition hover:bg-slate-50 focus-within:bg-slate-50">
        <td class="px-4 py-2">
            <div class="min-w-[12rem]">
                <a href="{{ route('visitors.show', $visitor) }}" class="block max-w-[14rem] min-[1600px]:max-w-[24rem] truncate font-medium text-slate-900 hover:text-brand-700 hover:underline">{{ $visitor->full_name }}</a>
                @if ($visitor->host_employee)
                    <div class="mt-1 max-w-[14rem] min-[1600px]:max-w-[24rem] truncate text-xs text-slate-500" title="Visiting: {{ $visitor->host_employee }}">Visiting: {{ $visitor->host_employee }}</div>
                @endif
                @if ($visitor->phone)
                    <div class="mt-1 text-xs text-slate-500">Phone: {{ $visitor->phone }}</div>
                @endif
                <div class="mt-1 text-xs min-[1600px]:text-sm text-slate-500">
                    @if ($visitor->check_in)
                        {{ $visitor->check_in->format('M j, Y') }} at {{ $visitor->check_in->format('h:i A') }}
                    @else
                        No check-in time
                    @endif
                </div>
            </div>
        </td>
        <td class="px-4 py-2 whitespace-nowrap text-slate-600">{{ $visitor->plate_number }}</td>
        <td class="px-4 py-2 text-right">
            <form method="POST" action="{{ route('visitors.checkout', $visitor) }}">
                @csrf
                <input type="hidden" name="return_to" value="dashboard">
                <button type="submit" class="whitespace-nowrap rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700" aria-label="Check out {{ $visitor->full_name }}">Check Out</button>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="3" class="px-6 py-10 text-center text-slate-500">
            {{ trim((string) request('visitor_q', '')) !== '' ? 'No checked-in visitors match your search.' : 'No visitors are currently checked in.' }}
        </td>
    </tr>
@endforelse
