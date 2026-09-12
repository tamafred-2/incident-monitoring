@props([
    'label' => '',
    'value' => null,
    'hint' => null,
    'tone' => 'default',
    'href' => null,
    'tinted' => false,
])

@php
    $tones = [
        'default' => ['value' => 'text-slate-900', 'dot' => 'bg-brand-500'],
        'emerald' => ['value' => 'text-emerald-600', 'dot' => 'bg-emerald-500'],
        'amber' => ['value' => 'text-amber-600', 'dot' => 'bg-amber-500'],
        'rose' => ['value' => 'text-rose-600', 'dot' => 'bg-rose-500'],
        'sky' => ['value' => 'text-brand-600', 'dot' => 'bg-brand-500'],
        'brand' => ['value' => 'text-brand-600', 'dot' => 'bg-brand-500'],
        'violet' => ['value' => 'text-violet-700', 'dot' => 'bg-violet-500'],
        'teal' => ['value' => 'text-teal-700', 'dot' => 'bg-teal-500'],
    ];
    $t = $tones[$tone] ?? $tones['default'];
    $surfaces = [
        'rose' => 'border-rose-200 bg-rose-50',
        'emerald' => 'border-emerald-200 bg-emerald-50',
        'amber' => 'border-amber-200 bg-amber-50',
        'brand' => 'border-brand-200 bg-brand-50',
        'violet' => 'border-violet-200 bg-violet-50',
        'teal' => 'border-teal-200 bg-teal-50',
    ];
    $surface = $tinted ? ($surfaces[$tone] ?? 'border-slate-200/80 bg-white') : 'border-slate-200/80 bg-white';
@endphp

<div {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-2xl border p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md ' . $surface]) }}>
    <div class="flex items-center gap-2">
        <span class="h-1.5 w-1.5 rounded-full {{ $t['dot'] }}"></span>
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $label }}</p>
    </div>
    <p class="mt-2 text-2xl font-bold tracking-tight tabular-nums {{ $t['value'] }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs {{ $tinted ? 'text-slate-600' : 'text-slate-400' }}">{{ $hint }}</p>
    @endif
    {{ $slot }}
    @if ($href)
        <a href="{{ $href }}" class="absolute inset-0 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500" aria-label="View {{ $label }}"></a>
    @endif
</div>
