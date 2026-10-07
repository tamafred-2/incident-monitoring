@props([
    'title' => '',
    'subtitle' => null,
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <h2 class="page-title">{{ $title }}</h2>
    @if ($subtitle)
        <p class="page-subtitle">{{ $subtitle }}</p>
    @endif
    {{ $slot }}
</div>
