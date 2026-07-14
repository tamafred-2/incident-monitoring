@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm']) }}>
    <div class="mb-3 flex items-start justify-between gap-3">
        <div>
            <h4 class="text-sm font-semibold text-slate-900">{{ $title }}</h4>
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </div>
    <div class="relative h-44">
        {{ $slot }}
    </div>
</div>
