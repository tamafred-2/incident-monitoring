@props(['name', 'records', 'modalTarget' => null])

<div data-scroll-list="{{ $name }}" x-data="recordList({ name: @js($name), nextUrl: @js($records->nextPageUrl()), loaded: {{ $records->count() }}, modalTarget: @js($modalTarget) })">
    <div x-ref="viewport" tabindex="0" role="region" aria-label="Scrollable {{ str_replace('-', ' ', $name) }}"
        class="h-[60dvh] overflow-auto overscroll-contain focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500"
        @scroll.passive="onScroll()" @wheel.passive="if ($event.deltaY > 0) onScroll()"
        @touchstart.passive="touchY = $event.touches[0].clientY"
        @touchmove.passive="if ($event.touches[0].clientY < touchY) onScroll()"
        @keydown="if (['ArrowDown', 'PageDown', 'End', ' '].includes($event.key) && $event.target === $el) onScroll()">
        {{ $slot }}
        <div x-show="nextUrl" class="h-px" aria-hidden="true"></div>
    </div>
    <div x-ref="footer" class="flex min-h-9 items-center justify-between gap-3 border-t border-slate-200 px-4 py-2 text-xs text-slate-500" aria-live="polite">
        <span x-text="message()">{{ $records->count() }} loaded</span>
        <button x-cloak x-show="error" type="button" @click="loadMore()" class="font-semibold text-brand-700 hover:underline">Retry</button>
        <noscript>
            @if ($records->hasMorePages())
                <a href="{{ $records->nextPageUrl() }}" class="font-semibold text-brand-700 underline">More records</a>
            @endif
        </noscript>
    </div>
</div>
