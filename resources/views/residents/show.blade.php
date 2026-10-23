@use('App\Enums\ActiveStatus')
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-page-header title="Resident Details" subtitle="Review the resident profile and assigned house information." />
            <a
                href="{{ route('residents.index', $indexContext) }}"
                class="px-4 py-2 text-sm font-semibold transition border rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50"
            >
                Back to Residents
            </a>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="page-shell flex flex-col gap-4 min-[1600px]:gap-6">
            @include('partials.alerts')

            @if (auth()->user()->isAdmin() && $resident->is_deceased && ($resident->relation_to_owner === 'Owner' || $ownershipTransfer))
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Transfer House Ownership</h3>
                    @if ($ownershipTransfer)
                        <p class="mt-2 text-sm text-slate-600">Ownership assigned to {{ $ownershipTransfer->new_owner_name }} on {{ \Illuminate\Support\Carbon::parse($ownershipTransfer->created_at)->format('M j, Y g:i A') }}. The previous owner's record is retained.</p>
                    @elseif ($successors->isNotEmpty())
                        <form method="POST" action="{{ route('residents.transfer-ownership', $resident) }}" class="mt-4 space-y-4">
                            @csrf
                            <p class="text-sm text-slate-600">Choose a living resident of this house. Confirming changes their household role to Owner and records the transfer.</p>
                            <div class="max-w-md">
                                <label for="successor" class="block text-sm font-medium">New owner</label>
                                <select data-ownership-successor id="successor" name="successor_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                                    <option value="">Select a resident</option>
                                    @foreach ($successors as $successor)
                                        <option data-name="{{ $successor->full_name }}" data-relationship="{{ $successor->relation_to_owner ?: 'Unspecified' }}" value="{{ $successor->getKey() }}" @selected(old('successor_id') == $successor->getKey())>{{ $successor->full_name }} — {{ $successor->relation_to_owner ?: 'Unspecified' }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('successor_id')" />
                            </div>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="confirm_transfer" value="1" required class="rounded border-slate-300">I confirm assigning this resident as the new owner.</label>
                            <x-input-error :messages="$errors->get('confirm_transfer')" />
                            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Assign New Owner</button>
                        </form>
                    @else
                        <p class="mt-2 text-sm text-slate-600">Add or activate a living resident in this house before assigning a new owner.</p>
                    @endif
                </section>
            @endif

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex flex-col gap-4 pb-5 border-b border-slate-200 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Resident Profile</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $resident->full_name }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $resident->subdivision?->subdivision_name ?? '-' }}</p>
                    </div>
                    @if (auth()->user()->isAdmin())
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $resident->status === ActiveStatus::Active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                        {{ $resident->is_deceased ? 'Deceased' : $resident->status->value }}
                    </span>
                    @endif
                </div>

                <div class="grid gap-6 mt-6 lg:grid-cols-2">
                    <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Resident Info</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Phone</dt>
                                <dd class="font-medium text-right text-slate-900">@include('residents.partials.phone-link')</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Email</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-all">{{ $resident->email ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Household Role</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-words">{{ $resident->relation_to_owner ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Created</dt>
                                <dd class="font-medium text-right text-slate-900">
                                    @if ($resident->created_at)
                                        <span class="block whitespace-nowrap">{{ $resident->created_at->format('M j, Y') }}</span>
                                        <span class="block mt-1 text-xs font-medium whitespace-nowrap text-slate-500">{{ $resident->created_at->format('h:i A') }}</span>
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="p-5 border rounded-2xl border-slate-200 bg-slate-50/70">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Housing and Access</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Street</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-words">{{ $resident->house?->street ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Block</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-words">{{ $resident->house?->block ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Lot</dt>
                                <dd class="max-w-[16rem] font-medium text-right text-slate-900 break-words">{{ $resident->house?->lot ?: '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
