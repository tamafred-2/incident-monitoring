<div
    x-data="adminVisitorNotifications({
        notifications: {{ \Illuminate\Support\Js::from(($adminVisitorNotifications ?? collect())->values()) }},
        unreadCount: {{ \Illuminate\Support\Js::from($adminVisitorUnreadCount ?? 0) }},
        notificationEndpoint: {{ \Illuminate\Support\Js::from(route('admin.visitor-notifications.index')) }},
        markReadEndpoint: {{ \Illuminate\Support\Js::from(route('admin.visitor-notifications.read-all')) }},
        markSingleReadEndpoint: {{ \Illuminate\Support\Js::from(route('admin.visitor-notifications.read-one')) }},
        clearAllEndpoint: {{ \Illuminate\Support\Js::from(route('admin.visitor-notifications.clear-all')) }}
    })"
    @click.away="open = false"
    class="relative z-20"
>
    <button
        type="button"
        @click="toggleDropdown()"
        class="relative inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white p-3 text-slate-700 shadow-sm transition hover:border-sky-200 hover:text-sky-700"
        aria-label="Open visitor notifications"
    >
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1.382a2 2 0 01-.553 1.382L4.293 10.01A1 1 0 005 11.707h10a1 1 0 00.707-1.697l-1.154-1.246A2 2 0 0114 8.382V7a4 4 0 00-4-4zM8.5 15a1.5 1.5 0 003 0h-3z" clip-rule="evenodd" />
        </svg>
        <span
            x-cloak
            x-show="unreadCount > 0"
            x-text="unreadCount > 9 ? '9+' : unreadCount"
            class="absolute -right-1.5 -top-1.5 inline-flex min-w-[1.35rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-semibold text-white"
        ></span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.right.duration.200ms
        class="absolute right-0 mt-3 w-[22rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl ring-1 ring-slate-900/5"
    >
        <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Visitor Notifications</p>
                    <p class="mt-1 text-xs text-slate-500">Recent visitor check-ins and check-outs.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600" x-text="`${notifications.length} items`"></span>
            </div>

            <div class="mt-4 flex gap-2">
                <button
                    type="button"
                    @click="markAllAsRead()"
                    :disabled="busy || unreadCount === 0"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Mark all as read
                </button>
                <button
                    type="button"
                    @click="clearAll()"
                    :disabled="busy || notifications.length === 0"
                    class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Clear all
                </button>
            </div>
        </div>

        <div class="max-h-[26rem] overflow-y-auto">
            <template x-if="notifications.length === 0">
                <div class="px-5 py-8 text-center text-sm text-slate-500">
                    No visitor notifications right now.
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.key">
                <a
                    :href="notification.detail_url"
                    @click.prevent="openNotification(notification)"
                    class="block border-b border-slate-100 px-5 py-4 transition last:border-b-0 hover:bg-slate-50"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-slate-900" x-text="notification.visitor_name"></p>
                                <span
                                    x-show="notification.is_unread"
                                    class="inline-flex h-2.5 w-2.5 rounded-full bg-sky-500"
                                ></span>
                            </div>
                            <p class="mt-1 text-xs leading-5 text-slate-500" x-text="notification.message"></p>
                            <p class="mt-2 text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400" x-text="notification.time_label"></p>
                        </div>
                        <span class="shrink-0 text-[11px] text-slate-400" x-text="notification.relative_time"></span>
                    </div>
                </a>
            </template>
        </div>
    </div>
</div>
