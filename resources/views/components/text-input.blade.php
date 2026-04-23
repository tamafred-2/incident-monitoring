@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-slate-300 bg-white/95 shadow-sm focus:border-sky-500 focus:ring-sky-500']) }}>
