<div class="mt-6 grid gap-5 md:grid-cols-2">
    @foreach (['investigation' => 'Investigation proof — Before', 'resolution' => 'Resolution proof — After', 'supporting' => 'Supporting images', 'legacy' => 'Existing evidence — stage not recorded'] as $stage => $heading)
        @php $stagePhotos = $proofPhotos->where('stage', $stage); @endphp
        @if ($stagePhotos->isNotEmpty() || in_array($stage, ['investigation', 'resolution']))
            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h4 class="font-semibold text-slate-700">{{ $heading }}</h4>
                @forelse ($stagePhotos as $photo)
                    <button type="button" @click="openPreview('{{ $photo['url'] }}', 'Evidence image')" class="mt-4 block w-full overflow-hidden rounded-xl border border-slate-200 text-left">
                        <img src="{{ $photo['url'] }}" alt="{{ $heading }}" class="h-56 w-full object-cover">
                        <span class="block p-3 text-xs text-slate-600">
                            @if ($stage !== 'legacy' && $photo['uploaded_at'])
                                Uploaded {{ $photo['uploaded_at']->format('M j, Y h:i:s A') }} ({{ config('app.timezone') }})
                            @else
                                Original upload time not verified.
                            @endif
                        </span>
                    </button>
                @empty
                    <p class="mt-3 text-sm text-slate-500">No evidence saved for this step yet.</p>
                @endforelse
            </section>
        @endif
    @endforeach
</div>
@if ($proofPhotos->isNotEmpty())
    <p class="mt-3 text-xs text-slate-500">Upload times record when files were received, not when photos were taken.</p>
@endif
