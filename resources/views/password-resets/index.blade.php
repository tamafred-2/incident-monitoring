<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Password Reset Requests</h2>
                <p class="mt-1 text-sm text-slate-500">Reset passwords for users who forgot theirs, then share the new password with them.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto flex max-w-5xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            @include('partials.alerts')

            {{-- One-time password reveal modal (shown once, right after a reset) --}}
            @if (session('reset_password_reveal'))
                @php $reveal = session('reset_password_reveal'); @endphp
                <div
                    x-data="{
                        open: true,
                        password: @js($reveal['password']),
                        copied: false,
                        copy() {
                            navigator.clipboard.writeText(this.password).then(() => {
                                this.copied = true;
                                setTimeout(() => this.copied = false, 2000);
                            });
                        }
                    }"
                    x-cloak
                    x-show="open"
                    x-transition.opacity
                    @keydown.escape.window="open = false"
                    class="fixed inset-0 z-50 flex items-center justify-center px-4"
                    role="dialog"
                    aria-modal="true"
                >
                    <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="open = false"></div>

                    <div
                        x-show="open"
                        x-transition.scale.origin.center
                        class="relative w-full max-w-md rounded-3xl bg-white p-7 text-center shadow-2xl"
                    >
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-7 w-7 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <h3 class="mt-4 text-lg font-semibold text-slate-900">New password generated</h3>
                        <p class="mt-1 text-sm text-slate-500">For {{ $reveal['email'] }}</p>

                        <div class="mt-5 flex items-center justify-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                            <code class="font-mono text-lg font-bold tracking-wide text-slate-900" x-text="password"></code>
                        </div>

                        <button
                            type="button"
                            @click="copy()"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.379a3 3 0 00-.879-2.121L10.5 5.379A3 3 0 008.379 4.5H7v-1z" />
                                <path d="M4.5 6A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h7a1.5 1.5 0 001.5-1.5v-5.879a1.5 1.5 0 00-.44-1.06L9.44 6.439A1.5 1.5 0 008.378 6H4.5z" />
                            </svg>
                            <span x-show="! copied">Copy password</span>
                            <span x-show="copied" x-cloak>Copied!</span>
                        </button>

                        <p class="mt-4 text-xs leading-5 text-slate-400">
                            Copy it now and give it to the user. For security it is shown only once &mdash; once you close, leave, or refresh this page it cannot be retrieved again.
                        </p>

                        <button
                            type="button"
                            @click="open = false"
                            class="mt-3 text-sm font-medium text-slate-500 transition hover:text-slate-700"
                        >
                            Close
                        </button>
                    </div>
                </div>
            @endif

            {{-- Pending requests --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Pending</h3>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                        {{ $pendingRequests->count() }} waiting
                    </span>
                </div>

                @forelse ($pendingRequests as $resetRequest)
                    <div class="flex flex-col gap-4 border-b border-slate-100 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900">{{ $resetRequest->user?->full_name ?? 'Unknown user' }}</p>
                            <p class="text-sm text-slate-500">{{ $resetRequest->email }}</p>
                            <p class="mt-1 text-xs text-slate-400">Requested {{ $resetRequest->updated_at->diffForHumans() }}</p>
                            @if ($resetRequest->expires_at)
                                <p class="mt-1 text-xs font-medium text-amber-600">Expires {{ $resetRequest->expires_at->diffForHumans() }} if not confirmed</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('admin.password-resets.resolve', $resetRequest) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700"
                                >
                                    Reset Password
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.password-resets.destroy', $resetRequest) }}"
                                  onsubmit="return confirm('Dismiss this request without resetting?');">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                                >
                                    Dismiss
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-400">No pending password reset requests.</p>
                @endforelse
            </div>

            {{-- Recent activity (completed + expired) --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="border-b border-slate-200 pb-4 text-lg font-semibold text-slate-900">Recent Activity</h3>

                @forelse ($recentRequests as $resetRequest)
                    <div class="flex flex-col gap-2 border-b border-slate-100 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900">{{ $resetRequest->user?->full_name ?? 'Unknown user' }}</p>
                            <p class="text-sm text-slate-500">{{ $resetRequest->email }}</p>
                            @if ($resetRequest->isExpired())
                                <p class="mt-1 text-xs text-slate-400">Expired {{ $resetRequest->updated_at?->diffForHumans() }}</p>
                            @else
                                <p class="mt-1 text-xs text-slate-400">
                                    Reset {{ $resetRequest->resolved_at?->diffForHumans() }}
                                    @if ($resetRequest->resolver) by {{ $resetRequest->resolver->full_name }} @endif
                                </p>
                            @endif
                        </div>

                        <div class="text-left sm:text-right">
                            @if ($resetRequest->isExpired())
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-12.5a.75.75 0 00-1.5 0V10c0 .2.08.39.22.53l2.5 2.5a.75.75 0 101.06-1.06l-2.28-2.28V5.5z" clip-rule="evenodd" />
                                    </svg>
                                    Expired (not confirmed in time)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    Password given to user
                                </span>
                                <p class="mt-1 text-xs text-slate-400">Password is shown once at reset and not stored.</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-400">No activity yet.</p>
                @endforelse

                @if ($recentRequests->hasPages())
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        {{ $recentRequests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
