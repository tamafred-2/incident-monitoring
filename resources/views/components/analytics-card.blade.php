@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'p-6 bg-white border shadow-sm rounded-2xl border-slate-200']) }}>
    <div class="mb-4">
        <h4 class="text-base font-semibold text-slate-900">{{ $title }}</h4>
        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="relative h-64">
        {{ $slot }}
    </div>
</div>