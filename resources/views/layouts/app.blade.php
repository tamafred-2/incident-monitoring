<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
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
                                <div class="shrink-0">
                                    @include('layouts.admin-visitor-notifications')
                                </div>
                            @endif
                        </div>
                    </header>
                @endisset

                @if (! isset($header) && auth()->user()?->isAdmin())
                    <div class="mx-auto flex max-w-7xl justify-end px-4 pt-6 sm:px-6 lg:px-8">
                        @include('layouts.admin-visitor-notifications')
                    </div>
                @endif

                <main class="pb-10">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
