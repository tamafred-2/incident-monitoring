@php
    use App\Models\Subdivision;
    use Illuminate\Support\Facades\Schema;

    $brandingSubdivision = null;

    if (Schema::hasTable('subdivisions')) {
        $brandingSubdivision = Subdivision::query()
            ->where('status', 'Active')
            ->orderBy('subdivision_name')
            ->first()
            ?? Subdivision::query()->orderBy('subdivision_name')->first();
    }

    $brandName = $brandingSubdivision?->subdivision_name ?? 'Doña Maria Dizon';
    $brandLogo = $brandingSubdivision?->logo_url ?? asset('imgsrc/logo.png');
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center']) }}>
    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="object-cover w-32 h-32 rounded-full">
    <span class="mt-2 text-2xl font-semibold text-slate-800 text-center">{{ $brandName }}</span>
</div>
