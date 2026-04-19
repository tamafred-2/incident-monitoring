@props(['subdivision' => null])

@php
    $lat = old('latitude',  $subdivision?->latitude  ?? '15.990612');
    $lng = old('longitude', $subdivision?->longitude ?? '120.366340');
    $zoom = 17;
    // OSM embed bbox: lng-0.005, lat-0.003, lng+0.005, lat+0.003
    $hasPin = $lat !== '' && $lng !== '';
    $embedUrl = $hasPin
        ? 'https://www.openstreetmap.org/export/embed.html?bbox=' . ($lng-0.005) . ',' . ($lat-0.003) . ',' . ($lng+0.005) . ',' . ($lat+0.003) . '&layer=mapnik&marker=' . $lat . ',' . $lng
        : 'https://www.openstreetmap.org/export/embed.html?bbox=120.361,15.887,120.371,15.893&layer=mapnik';
@endphp

<section
    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
    x-data="{
        lat: '{{ $lat }}',
        lng: '{{ $lng }}',
        searching: false,
        msg: '',
        get hasPin() { return this.lat !== '' && this.lng !== ''; },
        get embedUrl() {
            if (!this.hasPin) return 'https://www.openstreetmap.org/export/embed.html?bbox=120.361,15.887,120.371,15.893&layer=mapnik';
            const la = parseFloat(this.lat), ln = parseFloat(this.lng);
            return 'https://www.openstreetmap.org/export/embed.html?bbox='
                + (ln-0.005) + ',' + (la-0.003) + ',' + (ln+0.005) + ',' + (la+0.003)
                + '&layer=mapnik&marker=' + la + ',' + ln;
        },
        locate() {
            const street   = document.getElementById('f-street')?.value ?? '';
            const city     = document.getElementById('f-city')?.value ?? '';
            const province = document.getElementById('f-province')?.value ?? '';
            const zip      = document.getElementById('f-zip')?.value ?? '';
            const q = [street, city, province, zip, 'Philippines'].filter(Boolean).join(', ');
            if (!q.trim()) return;
            this.searching = true;
            this.msg = '';
            fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    this.searching = false;
                    if (data && data.length > 0) {
                        this.lat = parseFloat(data[0].lat).toFixed(6);
                        this.lng = parseFloat(data[0].lon).toFixed(6);
                        document.getElementById('f-latitude').value = this.lat;
                        document.getElementById('f-longitude').value = this.lng;
                        this.msg = 'Location found! Latitude and longitude updated.';
                    } else {
                        this.msg = 'Address not found. Try fewer words or enter coordinates manually.';
                    }
                })
                .catch(() => { this.searching = false; this.msg = 'Network error. Enter coordinates manually.'; });
        },
        clearPin() { this.lat = ''; this.lng = ''; document.getElementById('f-latitude').value = ''; document.getElementById('f-longitude').value = ''; this.msg = ''; },
        syncFromInputs() { 
            const lat = document.getElementById('f-latitude')?.value ?? '';
            const lng = document.getElementById('f-longitude')?.value ?? '';
            this.lat = lat;
            this.lng = lng;
        }
    }"
    @input.window="syncFromInputs()"
>

    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Map Location</h4>
            <p class="mt-1 text-sm text-slate-500">Search by address to fill in latitude and longitude automatically.</p>
        </div>
        <div class="flex shrink-0 gap-2">
            <button type="button" @click="locate()" :disabled="searching"
                class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-100 disabled:opacity-50">
                <span x-show="!searching">Locate from Address</span>
                <span x-show="searching" x-cloak>Searching…</span>
            </button>
            <button type="button" @click="clearPin()" x-show="hasPin" x-cloak
                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                Clear
            </button>
        </div>
    </div>

    <p x-show="msg" x-cloak x-text="msg" class="mb-3 text-xs text-amber-600"></p>

    {{-- OSM iframe preview — updates when lat/lng change --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200">
        <iframe
            :src="embedUrl"
            width="100%"
            height="300"
            frameborder="0"
            scrolling="no"
            class="w-full"
            style="border: none;"
        ></iframe>
    </div>

    <p class="mt-2 text-xs text-slate-400">
        Tip: Find your location on
        <a href="https://www.openstreetmap.org" target="_blank" class="text-sky-600 hover:underline">openstreetmap.org</a>,
        right-click → "Show address" to get coordinates, then paste them in the Latitude and Longitude fields above.
    </p>
</section>

