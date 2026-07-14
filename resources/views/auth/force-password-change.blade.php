<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-700">Account Security</p>

        <div class="mt-3 flex items-center gap-2">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('Update Password') }}</h2>

            {{-- Info icon: explains on hover why the user landed here --}}
            <span x-data="{ show: false }" class="relative inline-flex">
                <button
                    type="button"
                    @mouseenter="show = true"
                    @mouseleave="show = false"
                    @focus="show = true"
                    @blur="show = false"
                    class="flex h-5 w-5 items-center justify-center rounded-full text-slate-400 transition hover:text-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
                    aria-label="{{ __('Why am I seeing this?') }}"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                </button>

                <span
                    x-cloak
                    x-show="show"
                    x-transition
                    role="tooltip"
                    class="absolute left-0 top-full z-20 mt-2 w-64 rounded-xl bg-slate-900 px-3 py-2 text-left text-xs font-normal leading-5 text-white shadow-lg"
                >
                    {{ __('An administrator reset your password. For your security, you must set a new password before you can continue using the system.') }}
                </span>
            </span>
        </div>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </div>

    @if (session('warning'))
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700">
            {{ session('warning') }}
        </div>
    @endif

    <form
        method="post"
        action="{{ route('password.update') }}"
        class="space-y-5"
        x-data="{ password: '', confirmation: '' }"
    >
        @csrf
        @method('put')

        <div>
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" autofocus x-model="password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" x-model="confirmation" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />

            {{-- Live match feedback as the user types --}}
            <p x-cloak x-show="confirmation.length > 0 && confirmation !== password" class="mt-2 flex items-center gap-1.5 text-sm font-medium text-rose-600">
                <svg class="h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                {{ __('Passwords do not match') }}
            </p>
            <p x-cloak x-show="confirmation.length > 0 && confirmation === password" class="mt-2 flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                <svg class="h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                </svg>
                {{ __('Passwords match') }}
            </p>
        </div>

        <div class="flex justify-end pt-1">
            <x-primary-button
                x-bind:disabled="password.length === 0 || password !== confirmation"
                x-bind:class="(password.length === 0 || password !== confirmation) ? 'opacity-50 cursor-not-allowed' : ''"
            >{{ __('Save') }}</x-primary-button>
        </div>
    </form>

    <div class="mt-6 border-t border-slate-100 pt-5">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 015.25 2h5.5A2.25 2.25 0 0113 4.25v2a.75.75 0 01-1.5 0v-2a.75.75 0 00-.75-.75h-5.5a.75.75 0 00-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 00.75-.75v-2a.75.75 0 011.5 0v2A2.25 2.25 0 0110.75 18h-5.5A2.25 2.25 0 013 15.75V4.25z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M19 10a.75.75 0 00-.75-.75H8.704l1.048-.943a.75.75 0 10-1.004-1.114l-2.5 2.25a.75.75 0 000 1.114l2.5 2.25a.75.75 0 101.004-1.114l-1.048-.943h9.546A.75.75 0 0019 10z" clip-rule="evenodd" />
                </svg>
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
