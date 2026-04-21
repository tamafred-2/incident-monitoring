@php
    $streetOptions = ['Imperial Street', 'Plaza Boulevard'];
    $streetValue = old('street', $house->street ?? '');
    $streetSelection = in_array($streetValue, $streetOptions, true)
        ? $streetValue
        : ($streetValue !== '' ? 'others' : '');
@endphp

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
            value="{{ old('block', $house->block ?? '') }}"
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
            value="{{ old('lot', $house->lot ?? '') }}"
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
