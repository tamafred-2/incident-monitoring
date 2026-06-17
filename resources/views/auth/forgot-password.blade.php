<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-sky-700">Password Recovery</p>
        <p class="mt-3 text-sm leading-6 text-slate-500">
        {{ __('Forgot your password? Enter your email address to send a reset request. The administrator will reset your password and give you a new one.') }}
        </p>
    </div>

    <!-- Designed alert popups -->
    <x-popup-alert type="success" title="Request Sent" :message="session('status')" />
    <x-popup-alert type="error" title="Couldn't Send Request" :message="$errors->first('email')" />

    <form
        method="POST"
        action="{{ route('password.email') }}"
        x-data="{
            remaining: {{ (int) session('retry_after', 0) }},
            confirming: false,
            start() {
                if (this.remaining <= 0) return;
                const timer = setInterval(() => {
                    this.remaining--;
                    if (this.remaining <= 0) clearInterval(timer);
                }, 1000);
            }
        }"
        x-init="start()"
    >
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', request('email'))" required autofocus />

            <p x-cloak x-show="remaining > 0" class="mt-2 text-sm text-amber-600">
                {{ __('Too many requests. Please wait') }}
                <span class="font-semibold" x-text="remaining"></span>
                <span x-text="remaining === 1 ? '{{ __('second') }}' : '{{ __('seconds') }}'"></span>
                {{ __('before trying again.') }}
            </p>
        </div>

        <div class="mt-5 flex items-center justify-end">
            <x-primary-button
                type="button"
                @click="confirming = true"
                x-bind:disabled="remaining > 0"
                x-bind:class="remaining > 0 ? 'opacity-50 cursor-not-allowed' : ''"
            >
                <span x-show="remaining <= 0">{{ __('Request Password Reset') }}</span>
                <span x-cloak x-show="remaining > 0">{{ __('Try again in') }}&nbsp;<span x-text="remaining"></span>s</span>
            </x-primary-button>
        </div>

        <!-- Designed confirmation modal (replaces the native confirm dialog) -->
        <div
            x-cloak
            x-show="confirming"
            x-transition.opacity
            @keydown.escape.window="confirming = false"
            class="fixed inset-0 z-50 flex items-center justify-center px-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="confirming = false"></div>

            <div
                x-show="confirming"
                x-transition.scale.origin.center
                class="relative w-full max-w-sm rounded-3xl bg-white p-7 text-center shadow-2xl"
            >
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-sky-100">
                    <svg class="h-7 w-7 text-sky-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                </div>

                <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ __('Request a password reset?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    {{ __('The administrator will contact you to confirm that you forgot your password before resetting it. If you are not reached within :minutes minutes, the request will expire and you will need to request again.', ['minutes' => \App\Models\PasswordResetRequest::GRACE_MINUTES]) }}
                </p>

                <div class="mt-6 flex gap-3">
                    <button
                        type="button"
                        @click="confirming = false"
                        class="flex-1 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="$root.submit()"
                        class="flex-1 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                    >
                        {{ __('Yes, request reset') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-guest-layout>
