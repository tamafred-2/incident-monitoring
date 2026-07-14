@php
    $rememberedEmail = old('email', request()->cookie('remembered_email'));
@endphp

<x-guest-layout>
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-700">Account Access</p>
        <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Login</h2>
        <p class="mt-3 text-sm leading-6 text-slate-500">
            Sign in to continue to your monitoring dashboard.
        </p>
    </div>

    <x-popup-alert type="success" title="Request Sent" :message="session('status')" />

    @isset($quickLoginUser)
        <div class="mb-6 rounded-xl border border-brand-100 bg-brand-50/60 p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Welcome back</p>

            <form method="POST" action="{{ route('quick-login') }}" class="mt-4">
                @csrf
                <x-primary-button class="w-full justify-center">
                    {{ __('Continue as :name', ['name' => $quickLoginUser->full_name ?: $quickLoginUser->email]) }}
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('quick-login.forget') }}" class="mt-3 text-center">
                @csrf
                <button
                    type="submit"
                    class="text-sm font-medium text-slate-500 transition hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 rounded-md"
                >
                    {{ __('Use a different account') }}
                </button>
            </form>
        </div>
    @else
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                class="mt-2 block w-full"
                type="email"
                name="email"
                :value="$rememberedEmail"
                required
                autofocus
                autocomplete="username"
                placeholder="name@example.com"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between gap-4">
                <x-input-label for="password" :value="__('Password')" />

                @if (Route::has('password.request'))
                    <a
                        id="forgot-password-link"
                        data-base-url="{{ route('password.request') }}"
                        class="text-sm font-medium text-brand-700 transition hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 rounded-md"
                        href="{{ route('password.request') }}"
                    >
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <x-text-input
                id="password"
                class="mt-2 block w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4 pt-1">
            <label for="remember_me" class="inline-flex items-center gap-3 text-sm text-slate-600">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500"
                    name="remember"
                    @checked(old('remember', filled(request()->cookie('remembered_email'))))
                >
                <span>Remember me</span>
            </label>

            <x-primary-button class="min-w-[140px]">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
    @endisset

    <script>
        (function () {
            const emailInput = document.getElementById('email');
            const forgotLink = document.getElementById('forgot-password-link');

            if (!emailInput || !forgotLink) {
                return;
            }

            const baseUrl = forgotLink.dataset.baseUrl;

            const syncForgotLink = function () {
                const email = emailInput.value.trim();
                forgotLink.href = email
                    ? baseUrl + '?email=' + encodeURIComponent(email)
                    : baseUrl;
            };

            emailInput.addEventListener('input', syncForgotLink);
            syncForgotLink();
        })();
    </script>
</x-guest-layout>
