@if ($visitor->visit_deadline)
    <div class="mt-2 text-xs" x-data="{
        late: @js($visitor->is_overdue),
        timer: null,
        init() {
            @if (!$visitor->check_out)
                const update = () => { this.late = Date.now() > {{ $visitor->visit_deadline->getTimestampMs() }}; };
                update(); this.timer = setInterval(update, 1000);
            @endif
        },
        destroy() { if (this.timer) clearInterval(this.timer); }
    }">
        <span class="block text-slate-500">Limit: {{ $visitor->allowed_minutes }} minutes · Due {{ $visitor->visit_deadline->format('M j, g:i A') }}</span>
        <span x-show="late" @if (!$visitor->is_overdue) x-cloak @endif class="mt-1 inline-block rounded-full bg-rose-100 px-2 py-1 font-semibold text-rose-700">{{ $visitor->check_out ? 'Exceeded time limit' : 'Overdue - security follow-up needed' }}</span>
    </div>
@endif
