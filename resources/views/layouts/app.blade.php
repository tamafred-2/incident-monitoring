<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            use App\Models\Subdivision;
            use Illuminate\Support\Facades\Schema;

            $brandingSubdivision = null;

            if (Schema::hasTable('subdivisions')) {
                $brandingSubdivision = auth()->user()?->subdivision_id
                    ? Subdivision::find(auth()->user()->subdivision_id)
                    : null;

                $brandingSubdivision ??= Subdivision::query()
                    ->where('status', \App\Enums\ActiveStatus::Active)
                    ->orderBy('subdivision_name')
                    ->first()
                    ?? Subdivision::query()->orderBy('subdivision_name')->first();
            }

            $appBrandName = $brandingSubdivision?->subdivision_name ?? config('app.name', 'Laravel');
            $appBrandVersion = md5(($brandingSubdivision?->logo_path ?? 'default') . '|' . ($brandingSubdivision?->updated_at?->timestamp ?? 0));
            $appBrandIcon = route('branding.favicon.svg', ['v' => $appBrandVersion]);
            $appAppleIcon = route('branding.favicon', ['v' => $appBrandVersion]);
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appBrandName }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ $appBrandIcon }}">
        <link rel="apple-touch-icon" href="{{ $appAppleIcon }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-transparent">
            @include('layouts.navigation')

            <div class="lg:pl-72">
                @isset($header)
                    <header class="border-b border-slate-200/80 bg-transparent">
                        <div class="mx-auto flex max-w-7xl items-start justify-between gap-6 px-4 py-8 sm:px-6 lg:px-8">
                            <div class="min-w-0 flex-1">
                                {{ $header }}
                            </div>
                            @if (auth()->user()?->isAdmin())
                                <div class="flex-none">
                                    @include('layouts.admin-visitor-notifications')
                                </div>
                            @endif
                        </div>
                    </header>
                @endisset

                <main class="pb-10">
                    {{ $slot }}
                </main>

                {{-- Toast notifications --}}
                @if (session('success') || session('error') || session('warning'))
                <div
                    x-data="{
                        show: true,
                        type: '{{ session('error') ? 'error' : (session('warning') ? 'warning' : 'success') }}',
                        message: @js(session('error') ?? session('warning') ?? session('success')),
                        init() { setTimeout(() => this.show = false, 3000) }
                    }"
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="fixed bottom-6 right-6 z-[9999] flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium shadow-lg"
                    :class="{
                        'bg-emerald-600 text-white': type === 'success',
                        'bg-rose-600 text-white': type === 'error',
                        'bg-amber-500 text-white': type === 'warning',
                    }"
                >
                    <svg x-show="type === 'success'" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                    <svg x-show="type === 'error'" x-cloak class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
                    <svg x-show="type === 'warning'" x-cloak class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                    <span x-text="message"></span>
                    <button type="button" @click="show = false" class="ml-1 opacity-70 hover:opacity-100">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                    </button>
                </div>
                @endif
            </div>
        </div>
    </body>
</html>
