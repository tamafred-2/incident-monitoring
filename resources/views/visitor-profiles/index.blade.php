<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Visitor Profiles" subtitle="Register each visitor once, then add a visit whenever they return." />
    </x-slot>
    <div class="page-section"><div class="page-shell space-y-4">
        @include('partials.alerts')
        <div class="flex flex-wrap items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('visitor-profiles.index') }}" class="w-full sm:w-80">
                @if (request('subdivision_id'))<input type="hidden" name="subdivision_id" value="{{ request('subdivision_id') }}">@endif
                <label for="profile-search" class="sr-only">Search visitors</label>
                <input id="profile-search" name="q" value="{{ $q }}" type="search" placeholder="Search name or phone" data-live-search="#visitor-profile-results" autocomplete="off" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </form>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('expected-visits.index') }}" class="text-sm font-semibold text-brand-700">Expected Visits</a>
            @if (auth()->user()->isSecurity())
                <button type="button" x-data @click="$dispatch('open-modal', 'add-visitor-profile')" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Add Visitor</button>
            @endif
            </div>
        </div>
        <div id="visitor-profile-results" class="space-y-4">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <x-scroll-list name="visitor-profiles" :records="$profiles">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-left text-slate-600"><tr>
                    <th class="px-6 py-3 font-semibold">Name</th><th class="px-6 py-3 font-semibold">Phone</th><th class="px-6 py-3 font-semibold">Visits</th><th class="px-6 py-3 font-semibold">Status</th><th class="px-6 py-3 font-semibold">Action</th>
                </tr></thead>
                <tbody data-scroll-rows class="divide-y divide-slate-100">
                    @include('scroll-lists.visitor-profiles')
                </tbody>
            </table>
            </x-scroll-list>
        </div>
        </div>
        @if (auth()->user()->isSecurity())
            <x-modal name="add-visitor-profile" :show="$errors->any()" maxWidth="2xl" focusable>
                <form method="POST" action="{{ route('visitor-profiles.store') }}" enctype="multipart/form-data" class="space-y-5 bg-white p-6 sm:p-8">
                    @csrf
                    <input type="hidden" name="_submission_token" value="{{ bin2hex(random_bytes(16)) }}">
                    <h3 class="text-lg font-semibold text-slate-900">Add Visitor</h3>
                    <p class="text-sm text-slate-500">Save their identity and contact details. Visit details are recorded separately.</p>
                    @include('visitor-profiles.fields')
                    <div class="flex flex-wrap gap-3 pt-2"><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Save Visitor</button><button type="button" @click="$dispatch('close')" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button></div>
                </form>
            </x-modal>
        @endif
    </div></div>
    @include('partials.live-search')
</x-app-layout>
