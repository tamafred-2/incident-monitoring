@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $title }}</h2>
    @if ($subtitle)
        <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
    @endif
    {{ $slot }}
</div>