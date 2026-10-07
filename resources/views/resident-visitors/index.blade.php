@use('App\Enums\VisitorRequestStatus')
<x-app-layout>
    <x-slot name="header">
        <x-page-header title="My Visitors" subtitle="Respond to visitor requests so Admin/Guard can allow or deny entry using your confirmed resident response." />
    </x-slot>

    <div class="page-section">
        <div class="page-shell flex flex-col gap-4 min-[1600px]:gap-6">
            @include('partials.alerts')

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="pb-5 border-b border-slate-200">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">My Visitors</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Visitor Requests</h3>
                </div>

                @if ($requests->isEmpty())
                    <p class="mt-6 text-sm text-slate-500">No visitor requests yet.</p>
                @else
                    <x-scroll-list name="visitor-requests" :records="$requests" modal-target="#visitor-requests-scroll-modals">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="sticky top-0 z-10 bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">ID Photo</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Visitor</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Phone</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Purpose</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">House / Unit</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Requested</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Status</th>
                                    <th class="px-4 py-3 font-semibold text-left text-slate-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody data-scroll-rows class="bg-white divide-y divide-slate-100">
@include('scroll-lists.visitor-requests')
</tbody>
                        </table>
                    </x-scroll-list>
                @endif
            </div>
        </div>
    </div>

    <div id="visitor-requests-scroll-modals">@include('scroll-lists.visitor-requests-modals')</div>
</x-app-layout>

