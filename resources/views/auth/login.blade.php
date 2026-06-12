@php
    $rememberedEmail = old('email', request()->cookie('remembered_email'));
@endphp

<x-guest-layout>
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-700">Account Access</p>
        <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Login</h2>
        <p class="mt-3 text-sm leading-6 text-slate-500">
            Sign in to continue to your monitoring dashboard.
        </p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    @isset($quickLoginUser)
        <div class="mb-6 rounded-xl border border-sky-100 bg-sky-50/60 p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Welcome back</p>
            <p class="mt-2 text-sm text-slate-600">
                Continue as <span class="font-semibold text-slate-900">{{ $quickLoginUser->email }}</span>
            </p>

            <form method="POST" action="{{ route('quick-login') }}" class="mt-4">
                @csrf
                <x-primary-button class="w-full justify-center">
                    {{ __('Continue as :name', ['name' => $quickLoginUser->first_name ?: $quickLoginUser->email]) }}
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('quick-login.forget') }}" class="mt-3 text-center">
                @csrf
                <button
                    type="submit"
                    class="text-sm font-medium text-slate-500 transition hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 rounded-md"
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
                        class="text-sm font-medium text-sky-700 transition hover:text-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 rounded-md"
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
                    class="rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500"
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

</x-guest-layout>
