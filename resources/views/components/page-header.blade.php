@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <h2 class="text-2xl font-bold leading-tight tracking-tight text-slate-900">{{ $title }}</h2>
    @if ($subtitle)
        <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
    @endif
    {{ $slot }}
</div>