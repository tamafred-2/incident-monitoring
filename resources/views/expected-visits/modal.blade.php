<x-modal name="schedule-expected-visit" :show="request()->boolean('schedule') || (bool) old('expected_visit_form')" maxWidth="2xl" focusable>
        <form method="POST" action="{{ route('expected-visits.store', $profile) }}" class="space-y-5 bg-white p-6 sm:p-8">
            @csrf
            <input type="hidden" name="expected_visit_form" value="1">
            <div class="flex items-start justify-between gap-4">
                <div><h3 class="text-lg font-semibold text-slate-900">Schedule Expected Visit</h3><p class="mt-1 text-sm text-slate-500">Record the resident's call and expected arrival.</p></div>
                <button type="button" @click="$dispatch('close')" aria-label="Close expected visit modal" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Close</button>
            </div>
            <div><h3 class="text-lg font-semibold">{{ $profile->full_name }}</h3><p class="text-sm text-slate-500">{{ $profile->phone }}</p></div>
            @php
                $residentChoices = $residents->map(fn ($resident) => [
                    'id' => (string) $resident->getKey(),
                    'label' => $resident->full_name.' - '.$resident->house->display_address,
                ])->values();
            @endphp
            <div class="relative" x-data="{
                choices: @js($residentChoices),
                selectedId: @js((string) old('resident_id', '')),
                search: '', expanded: false, active: -1, visibleCount: 20, loading: false, generation: 0,
                init() {
                    const selected = this.choices.find(item => item.id === this.selectedId);
                    this.search = selected?.label || '';
                    if (!selected) this.selectedId = '';
                    this.$nextTick(() => this.validateSelection());
                },
                get matches() {
                    const query = this.search.trim().toLowerCase();
                    return this.choices.filter(item => this.selectedId || item.label.toLowerCase().includes(query));
                },
                get visibleMatches() {
                    return this.matches.slice(0, this.visibleCount);
                },
                get hasMore() {
                    return this.visibleCount < this.matches.length;
                },
                async loadMore() {
                    if (this.loading || !this.hasMore) return;
                    this.loading = true;
                    const generation = this.generation;
                    await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
                    if (generation !== this.generation) return;
                    this.visibleCount += 20;
                    this.loading = false;
                },
                onScroll() {
                    const list = this.$refs.results;
                    if (list.scrollTop + list.clientHeight >= list.scrollHeight - 48) this.loadMore();
                },
                resetResults() {
                    this.generation++; this.loading = false; this.visibleCount = 20;
                    this.$refs.results.scrollTop = 0;
                },
                validateSelection() {
                    this.$refs.search.setCustomValidity(this.selectedId ? '' : 'Choose a resident from the search results.');
                },
                edit() {
                    this.selectedId = ''; this.expanded = true; this.active = -1;
                    this.resetResults();
                    this.validateSelection();
                },
                choose(item) {
                    this.selectedId = item.id; this.search = item.label;
                    this.expanded = false; this.active = -1; this.resetResults(); this.validateSelection();
                },
                async move(step) {
                    this.expanded = true;
                    if (!this.matches.length || this.loading) return;
                    const next = Math.max(0, Math.min(this.active + step, this.matches.length - 1));
                    const generation = this.generation;
                    if (next >= this.visibleCount) await this.loadMore();
                    if (generation !== this.generation) return;
                    this.active = next;
                    this.$nextTick(() => document.getElementById('expected-resident-option-' + this.active)?.scrollIntoView({ block: 'nearest' }));
                }
            }" @click.outside="expanded = false" @focusout="if (!$el.contains($event.relatedTarget)) expanded = false">
                <label for="expected-resident" class="block text-sm font-medium">Resident who called / Host</label>
                <input type="hidden" name="resident_id" :value="selectedId">
                <input id="expected-resident" x-ref="search" type="text" x-model="search" required autocomplete="off"
                    placeholder="Search resident name or house address"
                    role="combobox" aria-autocomplete="list" aria-controls="expected-resident-results"
                    :aria-expanded="expanded" :aria-activedescendant="expanded && active >= 0 ? 'expected-resident-option-' + active : null"
                    @input="edit()" @focus="expanded = true" @click="expanded = true"
                    @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                    @keydown.enter.prevent="if (expanded && active >= 0 && matches[active]) choose(matches[active])"
                    @keydown.escape.stop.prevent="expanded = false"
                    class="mt-1 w-full rounded-xl border-slate-300">
                <div id="expected-resident-results" x-ref="results" role="listbox" x-show="expanded" x-cloak :aria-busy="loading" @scroll.passive="onScroll()"
                    class="absolute z-50 mt-1 max-h-52 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                    <template x-for="(item, index) in visibleMatches" :key="item.id">
                        <div role="option" :id="'expected-resident-option-' + index" :aria-selected="selectedId === item.id"
                            @mousedown.prevent @click="choose(item)" @mouseenter="active = index"
                            :class="active === index ? 'bg-brand-50 text-brand-800' : 'text-slate-700'"
                            class="cursor-pointer px-4 py-3 text-sm" x-text="item.label"></div>
                    </template>
                    <div x-show="loading" role="status" aria-live="polite" class="flex items-center justify-center gap-2 px-4 py-3 text-sm text-slate-500">
                        <span aria-hidden="true" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-brand-600"></span>
                        Loading more residents...
                    </div>
                    <p x-show="!loading && hasMore" class="px-4 py-2 text-center text-xs text-slate-400">Scroll for more residents</p>
                    <p x-show="!loading && !hasMore && matches.length > 0" class="px-4 py-2 text-center text-xs text-slate-400">All matching residents shown</p>
                    <p x-show="matches.length === 0" role="status" class="px-4 py-3 text-sm text-slate-500">No residents found.</p>
                </div>
                <x-input-error :messages="$errors->get('resident_id')" />
            </div>
            <div><label for="expected-time" class="block text-sm font-medium">Expected arrival</label>
                <input id="expected-time" type="datetime-local" name="expected_at" value="{{ old('expected_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required class="mt-1 w-full rounded-xl border-slate-300">
                <x-input-error :messages="$errors->get('expected_at')" />
            </div>
            <div><label for="expected-purpose" class="block text-sm font-medium">Purpose / Call notes</label><textarea id="expected-purpose" name="purpose" maxlength="2000" class="mt-1 w-full rounded-xl border-slate-300">{{ old('purpose') }}</textarea><x-input-error :messages="$errors->get('purpose')" /></div>
            <p class="text-sm text-slate-500">This records an expected visitor, not an entry. Security must verify the visitor and complete the normal check-in when they arrive.</p>
            <div class="flex gap-3"><button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Save Expected Visit</button><button type="button" @click="$dispatch('close')" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button></div>
        </form>
</x-modal>
