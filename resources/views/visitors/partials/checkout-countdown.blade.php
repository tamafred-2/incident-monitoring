@if ($deadline = $visitor->visit_deadline)
    @php
        $seconds = (int) ceil(now()->diffInSeconds($deadline, false));
        $overdue = $seconds < 0;
        $minutes = (int) ceil(abs($seconds) / 60);
        $label = $overdue ? 'Overdue by '.$minutes.' min' : ($seconds === 0 ? 'Due now' : $minutes.' min remaining');
    @endphp
    <div class="min-w-[12rem]" x-data="{
        seconds: @js($seconds),
        timer: null,
        get label() {
            const minutes = Math.ceil(Math.abs(this.seconds) / 60);
            return this.seconds < 0 ? 'Overdue by ' + minutes + ' min' : (this.seconds === 0 ? 'Due now' : minutes + ' min remaining');
        },
        init() {
            const start = Date.now();
            const initial = this.seconds;
            const update = () => { this.seconds = initial - Math.floor((Date.now() - start) / 1000); };
            this.timer = setInterval(update, 1000);
        },
        destroy() { clearInterval(this.timer); }
    }">
        <span class="inline-block rounded-full px-2 py-1 text-xs font-semibold"
            :class="seconds < 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'"
            x-text="label">{{ $label }}</span>
        <div class="mt-1 text-xs text-slate-500">Due {{ $deadline->format('M j, g:i A') }}</div>
    </div>
@else
    <span class="text-slate-500">No time limit set</span>
@endif
