@props([
    'status' => '',
])

@php
    $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $key = strtolower(trim($statusValue));
    $map = [
        // pending / in-progress
        'open' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'reported' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'pending' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'under investigation' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'investigating' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'inside' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        // resolved / positive
        'resolved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'closed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'done' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        // neutral / negative
        'checked out' => 'bg-slate-50 text-slate-600 ring-slate-500/20',
        'inactive' => 'bg-slate-50 text-slate-600 ring-slate-500/20',
        'declined' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
    ];
    $dotMap = [
        'open' => 'bg-amber-500', 'reported' => 'bg-amber-500', 'pending' => 'bg-amber-500',
        'under investigation' => 'bg-brand-500', 'investigating' => 'bg-brand-500', 'inside' => 'bg-brand-500',
        'resolved' => 'bg-emerald-500', 'closed' => 'bg-emerald-500', 'done' => 'bg-emerald-500',
        'completed' => 'bg-emerald-500', 'approved' => 'bg-emerald-500', 'active' => 'bg-emerald-500',
        'checked out' => 'bg-slate-400', 'inactive' => 'bg-slate-400', 'declined' => 'bg-rose-500',
    ];
    $classes = $map[$key] ?? 'bg-slate-50 text-slate-600 ring-slate-500/20';
    $dot = $dotMap[$key] ?? 'bg-slate-400';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$classes}"]) }}>
    <span class="h-1.5 w-1.5 flex-none rounded-full {{ $dot }}"></span>
    {{ $slot->isEmpty() ? $status : $slot }}
</span>
