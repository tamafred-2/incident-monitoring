import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.sidebarNavigation = ({ sectionOpenState }) => ({
    open: false,
    accountOpen: false,
    sections: sectionOpenState,
    init() {
        const savedSections = window.localStorage.getItem('sidebar-sections');

        if (savedSections) {
            try {
                this.sections = {
                    ...this.sections,
                    ...JSON.parse(savedSections),
                };
            } catch (error) {
                window.localStorage.removeItem('sidebar-sections');
            }
        }
    },
    toggleSection(section) {
        this.sections[section] = !this.sections[section];
        window.localStorage.setItem('sidebar-sections', JSON.stringify(this.sections));
    },
    toggleAccountMenu() {
        this.accountOpen = !this.accountOpen;
    },
});

window.adminVisitorNotifications = ({
    notifications,
    unreadCount,
    notificationEndpoint,
    markReadEndpoint,
    markSingleReadEndpoint,
    clearAllEndpoint,
}) => ({
    open: false,
    busy: false,
    notifications,
    unreadCount,
    notificationEndpoint,
    markReadEndpoint,
    markSingleReadEndpoint,
    clearAllEndpoint,
    init() {
        this.fetchNotifications();
        window.setInterval(() => this.fetchNotifications(), 30000);
    },
    csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    },
    async fetchNotifications() {
        if (!this.notificationEndpoint) {
            return;
        }

        try {
            const response = await fetch(this.notificationEndpoint, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.notifications = Array.isArray(data.notifications) ? data.notifications : [];
            this.unreadCount = Number(data.unread_count ?? 0);
        } catch (error) {
            console.error('Unable to refresh visitor notifications.', error);
        }
    },
    async toggleDropdown() {
        this.open = !this.open;

        if (this.open && this.unreadCount > 0) {
            await this.markAllAsRead();
        }
    },
    async markAllAsRead() {
        if (!this.markReadEndpoint || this.busy) {
            return;
        }

        this.busy = true;

        try {
            const response = await fetch(this.markReadEndpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                return;
            }

            this.unreadCount = 0;
            this.notifications = this.notifications.map((notification) => ({
                ...notification,
                is_unread: false,
            }));
        } finally {
            this.busy = false;
        }
    },
    async clearAll() {
        if (!this.clearAllEndpoint || this.busy) {
            return;
        }

        this.busy = true;

        try {
            const response = await fetch(this.clearAllEndpoint, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            this.notifications = [];
            this.unreadCount = 0;
            this.open = false;
        } finally {
            this.busy = false;
        }
    },
    async openNotification(notification) {
        if (!notification?.detail_url) {
            return;
        }

        if (notification.is_unread && this.markSingleReadEndpoint && !this.busy) {
            this.busy = true;

            try {
                const response = await fetch(this.markSingleReadEndpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        key: notification.key,
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    this.unreadCount = Number(data.unread_count ?? 0);
                    this.notifications = this.notifications.map((item) =>
                        item.key === notification.key
                            ? { ...item, is_unread: false }
                            : item
                    );
                }
            } finally {
                this.busy = false;
            }
        }

        window.location.href = notification.detail_url;
    },
});

Alpine.start();
