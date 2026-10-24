<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Password Reset Requests" subtitle="Reset passwords for users who forgot theirs, then share the new password with them." />
    </x-slot>

    <div class="page-section" x-data="{ activeTab: '{{ request()->query('tab') === 'history' ? 'history' : 'pending' }}' }">
        <div class="page-shell flex flex-col gap-4 min-[1600px]:gap-6">
            @include('partials.alerts')

            {{-- One-time password reveal modal (shown once, right after a reset) --}}
            @if (session('reset_password_reveal'))
                @php $reveal = session('reset_password_reveal'); @endphp
                <div
                    x-data="{
                        open: true,
                        password: @js($reveal['password']),
                        copied: false,
                        closeHighlighted: false,
                        highlightClose() {
                            if (!this.open) return;
                            this.closeHighlighted = true;
                            this.$refs.closeButton.focus();
                        },
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
                    @keydown.escape.window="if (open) { $event.preventDefault(); highlightClose(); }"
                    @keydown.tab="if (open) {
                        if ($event.shiftKey && document.activeElement === $refs.copyButton) {
                            $event.preventDefault(); $refs.closeButton.focus();
                        } else if (!$event.shiftKey && document.activeElement === $refs.closeButton) {
                            $event.preventDefault(); $refs.copyButton.focus();
                        }
                    }"
                    x-init="$nextTick(() => $refs.copyButton.focus())"
                    class="fixed inset-0 z-50 flex items-center justify-center px-4"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="reset-password-reveal-title"
                >
                    <div class="absolute inset-0 bg-slate-950/80" @click="highlightClose()" aria-hidden="true"></div>

                    <div
                        x-show="open"
                        x-transition.scale.origin.center
                        class="relative w-full max-w-md rounded-2xl bg-white p-7 text-center shadow-2xl"
                    >
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-7 w-7 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <h3 id="reset-password-reveal-title" class="mt-4 text-lg font-semibold text-slate-900">New password generated</h3>
                        <p class="mt-1 text-sm text-slate-500">For {{ $reveal['email'] }}</p>

                        <div class="mt-5 flex items-center justify-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                            <code class="font-mono text-lg font-bold tracking-wide text-slate-900" x-text="password"></code>
                        </div>

                        <button
                            type="button"
                            @click="copy()"
                            x-ref="copyButton"
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

                        <p x-show="closeHighlighted" x-cloak role="status" class="mt-3 text-xs font-medium text-amber-700">
                            Copy the password before clicking Close to dismiss this window.
                        </p>

                        <button
                            type="button"
                            x-ref="closeButton"
                            @click="open = false"
                            :class="closeHighlighted ? 'ring-4 ring-amber-400 ring-offset-2' : ''"
                            class="mt-3 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-amber-400 focus:ring-offset-2"
                        >
                            Close
                        </button>
                    </div>
                </div>
            @endif

            {{-- Tab switcher (same pattern as Visitor Check-out / Visitor History) --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 px-2 pb-1">
                    <nav class="flex flex-wrap gap-6" aria-label="Password reset sections">
                        <a
                            href="#pending-requests"
                            @click.prevent="activeTab = 'pending'"
                            :class="activeTab === 'pending' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                            class="flex items-center gap-2 border-b-2 px-1 pb-3 text-sm font-semibold transition"
                        >
                            Pending
                            @if ($pendingRequests->total() > 0)
                                <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-amber-100 px-1 text-[11px] font-semibold leading-none text-amber-700">
                                    {{ $pendingRequests->total() }}
                                </span>
                            @endif
                        </a>
                        <a
                            href="#recent-activity"
                            @click.prevent="activeTab = 'history'"
                            :class="activeTab === 'history' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                            class="border-b-2 px-1 pb-3 text-sm font-semibold transition"
                        >
                            Recent Activity
                        </a>
                    </nav>
                </div>
            </div>

            {{-- Pending requests --}}
            <section
                id="pending-requests"
                x-cloak
                x-show="activeTab === 'pending'"
                x-transition.opacity.duration.150ms
                class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Pending</h3>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                        {{ $pendingRequests->total() }} waiting
                    </span>
                </div>

                <x-scroll-list name="reset-pending" :records="$pendingRequests"><div data-scroll-rows>
@include('scroll-lists.reset-pending')
</div></x-scroll-list>
            </section>

            {{-- Recent activity (completed + expired) --}}
            <section
                id="recent-activity"
                x-cloak
                x-show="activeTab === 'history'"
                x-transition.opacity.duration.150ms
                class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <h3 class="border-b border-slate-200 pb-4 text-lg font-semibold text-slate-900">Recent Activity</h3>

                <x-scroll-list name="reset-history" :records="$recentRequests"><div data-scroll-rows>
@include('scroll-lists.reset-history')
</div></x-scroll-list>


            </section>
        </div>
    </div>
</x-app-layout>
