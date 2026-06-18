@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white p-4 shadow-none']) }}>
    <div class="mb-2">
        <h4 class="text-sm font-semibold text-slate-900">{{ $title }}</h4>
        @if ($subtitle)
            <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="relative h-44">
        {{ $slot }}
    </div>
</div>
