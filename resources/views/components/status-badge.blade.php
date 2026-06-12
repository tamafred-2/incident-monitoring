@props([
    'status' => '',
])

@php
    $key = strtolower(trim((string) $status));
    $map = [
        // pending / in-progress
        'open' => 'bg-amber-100 text-amber-700',
        'reported' => 'bg-amber-100 text-amber-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'under investigation' => 'bg-sky-100 text-sky-700',
        'investigating' => 'bg-sky-100 text-sky-700',
        'inside' => 'bg-sky-100 text-sky-700',
        // resolved / positive
        'resolved' => 'bg-emerald-100 text-emerald-700',
        'closed' => 'bg-emerald-100 text-emerald-700',
        'done' => 'bg-emerald-100 text-emerald-700',
        'completed' => 'bg-emerald-100 text-emerald-700',
        'approved' => 'bg-emerald-100 text-emerald-700',
        'active' => 'bg-emerald-100 text-emerald-700',
        // neutral / negative
        'checked out' => 'bg-slate-100 text-slate-600',
        'inactive' => 'bg-slate-100 text-slate-600',
        'declined' => 'bg-rose-100 text-rose-700',
    ];
    $classes = $map[$key] ?? 'bg-slate-100 text-slate-600';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex px-3 py-1 text-xs font-semibold rounded-full {$classes}"]) }}>
    {{ $slot->isEmpty() ? $status : $slot }}
</span>