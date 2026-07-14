@props([
    'message' => null,
    'type' => 'info',
    'title' => null,
])

@if (filled($message))
    @php
        $palette = [
            'success' => [
                'accent' => 'text-emerald-600',
                'iconBg' => 'bg-emerald-100',
                'button' => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500',
                'icon' => 'M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z',
                'defaultTitle' => 'Success',
            ],
            'error' => [
                'accent' => 'text-rose-600',
                'iconBg' => 'bg-rose-100',
                'button' => 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500',
                'icon' => 'M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.515 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z',
                'defaultTitle' => 'Something went wrong',
            ],
            'info' => [
                'accent' => 'text-brand-600',
                'iconBg' => 'bg-brand-100',
                'button' => 'bg-brand-600 hover:bg-brand-700 focus:ring-brand-500',
                'icon' => 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z',
                'defaultTitle' => 'Notice',
            ],
        ];
        $p = $palette[$type] ?? $palette['info'];
        $heading = $title ?? $p['defaultTitle'];
    @endphp

    <div
        x-data="{ open: true }"
        x-cloak
        x-show="open"
        x-transition.opacity
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center px-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-slate-950/80" @click="open = false"></div>

        <div
            x-show="open"
            x-transition.scale.origin.center
            class="relative w-full max-w-sm rounded-2xl bg-white p-7 text-center shadow-2xl"
        >
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full {{ $p['iconBg'] }}">
                <svg class="h-7 w-7 {{ $p['accent'] }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="{{ $p['icon'] }}" clip-rule="evenodd" />
                </svg>
            </div>

            <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $heading }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">{{ $message }}</p>

            <button
                type="button"
                @click="open = false"
                class="mt-6 inline-flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $p['button'] }}"
            >
                Got it
            </button>
        </div>
    </div>
@endif
