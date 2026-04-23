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
            $appBrandLogo = $brandingSubdivision?->logo_url ?? asset('imgsrc/logo.png');
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appBrandName }}</title>
        <link rel="icon" type="image/png" href="{{ $appBrandIcon }}">
        <link rel="apple-touch-icon" href="{{ $appBrandIcon }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .guest-page {
                min-height: 100vh;
                background:
                    radial-gradient(circle at top left, rgba(127, 179, 255, 0.18), transparent 24%),
                    linear-gradient(180deg, #f5f7fb 0%, var(--shell-page) 100%);
                font-family: 'Outfit', sans-serif;
            }

            .guest-shell {
                width: min(520px, calc(100% - 32px));
                margin: 0 auto;
                padding: 48px 0;
            }

            .guest-header {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                margin-bottom: 24px;
            }

            .guest-logo {
                width: 86px;
                height: 86px;
                border-radius: 999px;
                overflow: hidden;
                background: #ffffff;
                border: 1px solid var(--shell-border);
                box-shadow: 0 18px 34px rgba(15, 23, 42, 0.08);
            }

            .guest-logo img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .guest-kicker {
                margin-top: 18px;
                color: #0369a1;
                font-size: 0.78rem;
                font-weight: 700;
                letter-spacing: 0.18em;
                text-transform: uppercase;
            }

            .guest-title {
                margin: 10px 0 0;
                font-size: clamp(1.9rem, 4vw, 2.6rem);
                font-weight: 800;
                line-height: 1.05;
                letter-spacing: -0.03em;
                color: #0d1726;
            }

            .guest-subtitle {
                margin: 12px 0 0;
                max-width: 460px;
                color: #64748b;
                font-size: 0.98rem;
                line-height: 1.7;
            }

            .guest-card {
                border: 1px solid var(--shell-border);
                border-radius: 40px;
                background: rgba(255, 255, 255, 0.96);
                box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
                padding: 34px;
            }

            @media (max-width: 640px) {
                .guest-shell {
                    padding: 28px 0;
                }

                .guest-card {
                    padding: 24px;
                    border-radius: 30px;
                }
            }
        </style>
    </head>
    <body class="guest-page text-slate-900 antialiased">
        <div class="guest-shell">
            <div class="guest-header">
                <a href="/" class="guest-logo">
                    <img src="{{ $appBrandLogo }}" alt="{{ $appBrandName }} logo">
                </a>
                <div class="guest-kicker">Monitoring Platform</div>
                <h1 class="guest-title">{{ $appBrandName }}</h1>
                <p class="guest-subtitle">
                    Access the system for visitor monitoring, resident records, and incident coordination.
                </p>
            </div>

            <div class="guest-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
