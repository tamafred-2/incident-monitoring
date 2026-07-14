@props([
    'label' => '',
    'value' => null,
    'hint' => null,
    'tone' => 'default',
])

@php
    $tones = [
        'default' => ['value' => 'text-slate-900', 'dot' => 'bg-brand-500'],
        'emerald' => ['value' => 'text-emerald-600', 'dot' => 'bg-emerald-500'],
        'amber' => ['value' => 'text-amber-600', 'dot' => 'bg-amber-500'],
        'rose' => ['value' => 'text-rose-600', 'dot' => 'bg-rose-500'],
        'sky' => ['value' => 'text-brand-600', 'dot' => 'bg-brand-500'],
        'brand' => ['value' => 'text-brand-600', 'dot' => 'bg-brand-500'],
    ];
    $t = $tones[$tone] ?? $tones['default'];
@endphp

<div {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md']) }}>
    <div class="flex items-center gap-2">
        <span class="h-1.5 w-1.5 rounded-full {{ $t['dot'] }}"></span>
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $label }}</p>
    </div>
    <p class="mt-2 text-2xl font-bold tracking-tight tabular-nums {{ $t['value'] }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif
    {{ $slot }}
</div>
