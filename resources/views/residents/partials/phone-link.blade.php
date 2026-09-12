@if ($resident->phone)
    <span class="text-sm font-medium text-slate-700">{{ $resident->phone }}</span>
@else
    <span class="text-sm text-slate-500">No phone provided</span>
@endif
