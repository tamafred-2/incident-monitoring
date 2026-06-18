@props([
    'label' => '',
    'value' => null,
    'hint' => null,
    'tone' => 'default',
])

@php
    $toneClasses = [
        'default' => 'text-slate-900',
        'emerald' => 'text-emerald-600',
        'amber' => 'text-amber-600',
        'rose' => 'text-rose-600',
        'sky' => 'text-sky-600',
    ][$tone] ?? 'text-slate-900';
@endphp

<div {{ $attributes->merge(['class' => 'p-4 bg-white border shadow-sm rounded-2xl border-slate-200']) }}>
    <p class="text-xs text-slate-500">{{ $label }}</p>
    <p class="mt-1.5 text-2xl font-bold {{ $toneClasses }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-0.5 text-xs text-slate-400">{{ $hint }}</p>
    @endif
    {{ $slot }}
</div>