@php
    $selectedSubdivision = old('subdivision_id', $house->subdivision_id ?? '');
    $block = old('block', $house->block ?? '');
    $lot = old('lot', $house->lot ?? '');
@endphp

<div>
    <label class="block text-sm font-medium text-slate-700">Subdivision</label>
    <select name="subdivision_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
        <option value="">Select subdivision</option>
        @foreach ($subdivisions as $subdivisionOption)
            <option value="{{ $subdivisionOption->subdivision_id }}" @selected((string) $selectedSubdivision === (string) $subdivisionOption->subdivision_id)>
                {{ $subdivisionOption->subdivision_name }}
            </option>
        @endforeach
    </select>
    @error('subdivision_id')
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-slate-700">Block</label>
        <input
            type="text"
            name="block"
            value="{{ $block }}"
            required
            maxlength="30"
            placeholder="e.g. 3"
            class="mt-1 w-full rounded-xl border-slate-300 text-sm uppercase shadow-sm focus:border-sky-500 focus:ring-sky-500"
        >
        @error('block')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Lot</label>
        <input
            type="text"
            name="lot"
            value="{{ $lot }}"
            required
            maxlength="30"
            placeholder="e.g. 12"
            class="mt-1 w-full rounded-xl border-slate-300 text-sm uppercase shadow-sm focus:border-sky-500 focus:ring-sky-500"
        >
        @error('lot')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>
