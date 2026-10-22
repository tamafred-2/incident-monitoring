<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-page-header :title="$profile->full_name" :subtitle="$profile->visitor_number" />
            <a href="{{ route('visitor-profiles.index', ['subdivision_id' => $profile->subdivision_id]) }}" class="text-sm font-semibold text-brand-700">Back to Visitor Profiles</a>
        </div>
    </x-slot>
    <div class="page-section"><div class="page-shell space-y-4">
        @include('partials.alerts')
        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-slate-900">Visitor Information</h3>
                @if (auth()->user()->hasRole('security'))<button type="button" x-data @click="$dispatch('open-modal', 'edit-visitor-profile')" class="inline-flex items-center rounded-xl border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 transition hover:border-brand-300 hover:bg-brand-100 hover:text-brand-800">Edit Profile</button>@endif
            </div>
            <dl class="mt-5 grid gap-5 sm:grid-cols-3">
                <div><dt class="text-sm text-slate-500">Name</dt><dd class="mt-1 font-medium text-slate-900">{{ $profile->full_name }}</dd></div>
                <div><dt class="text-sm text-slate-500">Phone</dt><dd class="mt-1 text-slate-900">{{ $profile->phone }}</dd></div>
                <div><dt class="text-sm text-slate-500">Status</dt><dd class="mt-1 text-slate-900">{{ $isInside ? 'Currently inside' : 'Outside' }}</dd></div>
            </dl>
            @if ($profile->id_photo_path && auth()->user()->hasRole('security'))
                <button type="button" x-data @click="$dispatch('open-modal', 'visitor-id-photo')" class="mt-5 inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900">View ID Photo</button>
                <x-modal name="visitor-id-photo" maxWidth="3xl" focusable>
                    <div role="dialog" aria-modal="true" aria-labelledby="visitor-id-photo-title" class="p-6">
                        <div class="flex items-center justify-between gap-4">
                            <h3 id="visitor-id-photo-title" class="text-lg font-semibold text-slate-900">Visitor ID Photo</h3>
                            <button type="button" @click="$dispatch('close')" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Close</button>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $profile->full_name }}</p>
                        <template x-if="show">
                            <img src="{{ route('visitor-profiles.photo', $profile) }}" alt="ID photo of {{ $profile->full_name }}" class="mx-auto mt-5 max-h-[70dvh] w-full rounded-xl object-contain">
                        </template>
                    </div>
                </x-modal>
            @endif
        </section>
        @if ($expectedVisits->isNotEmpty())
        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <h3 class="text-lg font-semibold text-slate-900">Expected Visit Records</h3>
            <p class="mt-1 text-sm text-slate-500">The 10 most recent scheduled visits, including arrivals and cancellations.</p>
            <div class="mt-4 divide-y divide-slate-100">
                @foreach ($expectedVisits as $expected)
                <div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                    <div><p class="font-semibold">{{ $expected->expected_at->format('M j, Y h:i A') }} - {{ $expected->status }}</p><p class="text-slate-500">Resident: {{ $expected->resident?->full_name ?? 'Unavailable' }} - Recorded by {{ $expected->recorder?->full_name ?? 'Unknown' }}</p></div>
                    @if ($expected->status === 'Expected' && auth()->user()->hasRole('security'))<a href="{{ route('visitors.index', ['expected_visit' => $expected->getKey()]) }}" class="font-semibold text-brand-700">Check In on Arrival</a>@endif
                </div>
                @endforeach
            </div>
        </section>
        @endif
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
                <h3 class="text-lg font-semibold text-slate-900">Visit History</h3>
                @if (auth()->user()->hasRole('security'))
                    <div class="flex flex-wrap items-center gap-2">
                    <button type="button" x-data @click="$dispatch('open-modal', 'schedule-expected-visit')" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Schedule Expected Visit</button>
                    @if ($isInside)<span class="text-sm text-amber-700">Check out the current visit before adding another.</span>
                    @else<a href="{{ route('visitors.index', ['profile' => $profile->getKey(), 'subdivision_id' => $profile->subdivision_id]) }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Add Visit</a>@endif
                    </div>
                @endif
            </div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600"><tr>
                    @foreach (['Type / Destination', 'Purpose', 'Vehicle / Plate', 'Check In', 'Check Out', 'Duration', 'Owner Permission', 'Status'] as $heading)<th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($visits as $visit)
                        <tr>
                            <td class="px-4 py-4"><div>{{ $visit->host_employee ? 'Resident Visit' : 'Walk-in' }}</div><div class="mt-1 text-xs text-slate-500">{{ $visit->host_employee }} {{ $visit->house_address_or_unit }}</div></td>
                            <td class="px-4 py-4">{{ $visit->purpose }}</td><td class="px-4 py-4">{{ $visit->vehicle_type }} {{ $visit->vehicle_color }} {{ $visit->plate_number }}</td>
                            <td class="whitespace-nowrap px-4 py-4">{{ $visit->check_in?->format('M j, Y h:i A') }}</td><td class="whitespace-nowrap px-4 py-4">{{ $visit->check_out?->format('M j, Y h:i A') }}</td>
                            <td class="whitespace-nowrap px-4 py-4">{{ $visit->visit_duration_label }}</td>
                            <td class="px-4 py-4">
                                @if ($visit->owner_approval)
                                    {{ $visit->owner_approval['owner_name'] }}
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ ($visit->owner_approval['call_status'] ?? 'approved') === 'unavailable' ? 'Not called / owner unavailable - entry without owner approval' : 'Approved by phone' }}
                                        - {{ \Illuminate\Support\Carbon::parse($visit->owner_approval['recorded_at'] ?? $visit->owner_approval['approved_at'])->format('M j, Y h:i A') }}
                                        <br>Recorded by {{ $visit->owner_approval['recorded_by_name'] }}
                                    </div>
                                @elseif ($visit->host_employee) Not recorded @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">{{ $visit->status->value }}
                                @if ($visit->trashed())<span class="block text-xs text-slate-500">Archived</span>@endif
                                @if (!$visit->trashed() && $visit->status === \App\Enums\VisitorStatus::Inside && auth()->user()->hasRole('security'))
                                    <form method="POST" action="{{ route('visitors.checkout', $visit) }}" class="mt-2">@csrf<input type="hidden" name="return_to" value="profile"><button class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800">Check Out</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty<tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">No visits yet. Add a visit when this visitor arrives.</td></tr>@endforelse
                </tbody>
            </table></div>
            <div class="p-4">{{ $visits->links() }}</div>
        </section>
        @if (auth()->user()->hasRole('security'))
            @include('expected-visits.modal')
            <x-modal name="edit-visitor-profile" :show="$errors->any() && !old('expected_visit_form')" maxWidth="2xl" focusable>
                <form method="POST" action="{{ route('visitor-profiles.update', $profile) }}" enctype="multipart/form-data" class="space-y-5 bg-white p-6 sm:p-8">
                    @csrf @method('PUT')
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Edit Visitor Profile</h3>
                            <p class="mt-1 text-sm text-slate-500">Update the visitor's identity, contact details, and ID photo.</p>
                        </div>
                        <button type="button" @click="$dispatch('close')" aria-label="Close edit visitor profile modal" class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
                        </button>
                    </div>
                    @include('visitor-profiles.fields')
                    <div class="flex flex-wrap gap-3 pt-2"><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Save Profile</button><button type="button" @click="$dispatch('close')" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button></div>
                </form>
            </x-modal>
        @endif
    </div></div>
</x-app-layout>
