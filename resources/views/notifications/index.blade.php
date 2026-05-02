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

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-500">
                            @if ($notifications->total() > 0)
                                Showing {{ $notifications->firstItem() }}-{{ $notifications->lastItem() }} of {{ $notifications->total() }} notifications
                            @else
                                No notifications to paginate
                            @endif
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($notifications->onFirstPage())
                                <span class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-400"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></span>
                            @else
                                <a href="{{ $notifications->previousPageUrl() }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&larr;</span><span class="sr-only">Previous</span></a>
                            @endif
                            <span class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">
                                Page {{ $notifications->currentPage() }} of {{ max($notifications->lastPage(), 1) }}
                            </span>
                            @if ($notifications->hasMorePages())
                                <a href="{{ $notifications->nextPageUrl() }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></a>
                            @else
                                <span class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-400"><span aria-hidden="true">&rarr;</span><span class="sr-only">Next</span></span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
