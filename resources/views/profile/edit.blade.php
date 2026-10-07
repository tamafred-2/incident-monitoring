<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="page-section">
        <div class="page-shell grid gap-4 xl:grid-cols-2 min-[1600px]:gap-6">
            <div class="p-4 min-[1600px]:p-6 bg-white border border-slate-200 shadow-sm rounded-2xl">
                <div class="w-full">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 min-[1600px]:p-6 bg-white border border-slate-200 shadow-sm rounded-2xl">
                <div class="w-full">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
