@php
    $selectedSubdivision = old('subdivision_id', $house->subdivision_id ?? '');
    if ($selectedSubdivision === '' || $selectedSubdivision === null) {
        $selectedSubdivision = $subdivisions->first()?->subdivision_id ?? '';
    }
    $selectedSubdivisionName = $subdivisions->firstWhere('subdivision_id', (int) $selectedSubdivision)?->subdivision_name
        ?? 'Subdivision';
    $streetOptions = ['Imperial Street', 'Plaza Boulevard'];
    $streetValue = old('street', $house->street ?? '');
    $streetSelection = in_array($streetValue, $streetOptions, true)
        ? $streetValue
        : ($streetValue !== '' ? 'others' : '');
    $block = old('block', $house->block ?? '');
    $lot = old('lot', $house->lot ?? '');
@endphp

<div>
    <label class="block text-sm font-medium text-slate-700">Subdivision</label>
    <input type="hidden" name="subdivision_id" value="{{ $selectedSubdivision }}">
    <div class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
        {{ $selectedSubdivisionName }}
    </div>
    @error('subdivision_id')
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>

<div class="grid gap-4 md:grid-cols-2" x-data="{ streetSelection: '{{ $streetSelection }}' }">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Street</label>
        <select
            x-model="streetSelection"
            required
            class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
        >
            <option value="">Select Street</option>
            @foreach ($streetOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
            <option value="others">Others</option>
        </select>

        <template x-if="streetSelection === 'others'">
            <input
                type="text"
                name="street"
                required
                maxlength="120"
                value="{{ $streetSelection === 'others' ? $streetValue : '' }}"
                placeholder="Type street name"
                class="mt-3 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
            >
        </template>

        <template x-if="streetSelection !== 'others'">
            <input type="hidden" name="street" :value="streetSelection">
        </template>

        @error('street')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
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
