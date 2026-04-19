<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
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

            $appBrandName = $brandingSubdivision?->subdivision_name ?? config('app.name', 'Laravel');
            $appBrandVersion = md5(($brandingSubdivision?->logo_path ?? 'default') . '|' . ($brandingSubdivision?->updated_at?->timestamp ?? 0));
            $appBrandIcon = route('branding.favicon', ['v' => $appBrandVersion]);
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appBrandName }}</title>
        <link rel="icon" type="image/png" href="{{ $appBrandIcon }}">
        <link rel="apple-touch-icon" href="{{ $appBrandIcon }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="text-gray-500 max-w-xs sm:max-w-md px-4" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
