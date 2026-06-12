@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white border shadow-sm rounded-2xl border-slate-200']) }}>
    @if ($title || $subtitle)
        <div class="px-6 py-4 border-b border-slate-200">
            @if ($title)
                <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
            @endif
            @if ($subtitle)
                <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>