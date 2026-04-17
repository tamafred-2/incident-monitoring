<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifications</h2>
                <p class="mt-1 text-sm text-slate-500">Assignment, verification, and status updates for your incident reports.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('dashboard') }}"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @if ($notifications->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">
                        You have no notifications at the moment.
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($notifications as $notification)
                            <article class="rounded-2xl border border-slate-200 p-4 {{ $notification->read_at ? 'bg-white' : 'bg-sky-50' }}">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $notification->data['message'] ?? '' }}</p>
                                    </div>
                                    <div class="text-right text-xs text-slate-500">
                                        {{ optional($notification->created_at)->format('M j, Y h:i A') }}
                                    </div>
                                </div>
                                @if (isset($notification->data['url']))
                                    <div class="mt-4">
                                        <a href="{{ $notification->data['url'] }}" class="text-sky-600 hover:text-sky-700 text-sm font-semibold">View incident</a>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
