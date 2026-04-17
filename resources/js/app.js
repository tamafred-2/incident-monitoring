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

const MODAL_ROOT_SELECTOR = 'div[x-on\\:open-modal\\.window]';
const INDICATOR_SELECTOR = '[data-field-requirement-indicator]';
const PASSWORD_TOGGLE_ATTR = 'data-password-toggle-enhanced';

const isSupportedControl = (element) => {
    if (!element) {
        return false;
    }

    if (!(element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement)) {
        return false;
    }

    if (element instanceof HTMLInputElement) {
        const unsupportedTypes = ['hidden', 'checkbox', 'radio', 'submit', 'reset', 'button', 'image'];
        return !unsupportedTypes.includes(element.type);
    }

    return true;
};

const resolveLabelControl = (label, form) => {
    if (!(label instanceof HTMLLabelElement)) {
        return null;
    }

    if (label.htmlFor) {
        const linkedControl = form.querySelector(`#${CSS.escape(label.htmlFor)}`);
        return isSupportedControl(linkedControl) ? linkedControl : null;
    }

    const directControl = label.parentElement?.querySelector('input, select, textarea');
    if (isSupportedControl(directControl)) {
        return directControl;
    }

    const nestedControl = label.querySelector('input, select, textarea');
    if (isSupportedControl(nestedControl)) {
        return nestedControl;
    }

    return null;
};

const upsertRequirementIndicator = (label, required) => {
    let indicator = label.querySelector(INDICATOR_SELECTOR);

    if (!indicator) {
        indicator = document.createElement('span');
        indicator.dataset.fieldRequirementIndicator = 'true';
        indicator.className = 'ml-1 text-xs font-medium';
        label.appendChild(indicator);
    }

    if (required) {
        if (indicator.textContent !== '*') {
            indicator.textContent = '*';
        }
        indicator.classList.remove('text-slate-400');
        indicator.classList.add('text-rose-600');
        return;
    }

    if (indicator.textContent !== '(optional)') {
        indicator.textContent = '(optional)';
    }
    indicator.classList.remove('text-rose-600');
    indicator.classList.add('text-slate-400');
};

const annotateModalFormLabels = (root) => {
    const forms = root.querySelectorAll('form');

    forms.forEach((form) => {
        const labels = form.querySelectorAll('label');

        labels.forEach((label) => {
            const control = resolveLabelControl(label, form);
            if (!control) {
                return;
            }

            upsertRequirementIndicator(label, Boolean(control.required));
        });
    });
};

const initializeModalFieldIndicators = () => {
    const modalRoots = document.querySelectorAll(MODAL_ROOT_SELECTOR);
    modalRoots.forEach((root) => annotateModalFormLabels(root));

    const observer = new MutationObserver((mutations) => {
        const visitedRoots = new Set();

        mutations.forEach((mutation) => {
            const element = mutation.target;
            if (!(element instanceof Element)) {
                return;
            }

            const modalRoot = element.closest(MODAL_ROOT_SELECTOR);
            if (!modalRoot || visitedRoots.has(modalRoot)) {
                return;
            }

            visitedRoots.add(modalRoot);
            annotateModalFormLabels(modalRoot);
        });
    });

    observer.observe(document.body, {
        subtree: true,
        attributes: true,
        attributeFilter: ['required'],
    });
};

const EYE_ICON = `
<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
    <circle cx="12" cy="12" r="3" />
</svg>
`;

const EYE_OFF_ICON = `
<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.43 10.43 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a17.7 17.7 0 01-4.17 5.3M6.24 6.24A17.8 17.8 0 002.25 12s3.75 7.5 9.75 7.5a10.6 10.6 0 003.08-.46" />
</svg>
`;

const setPasswordToggleIcon = (button, isVisible) => {
    button.innerHTML = isVisible ? EYE_OFF_ICON : EYE_ICON;
    button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
};

const enhancePasswordField = (input) => {
    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    if (input.getAttribute('type') !== 'password' || input.hasAttribute(PASSWORD_TOGGLE_ATTR)) {
        return;
    }

    const parent = input.parentElement;
    if (!parent) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'relative';
    wrapper.dataset.passwordToggleWrapper = 'true';

    parent.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    if (!input.classList.contains('pr-16')) {
        input.classList.add('pr-16');
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'absolute inset-y-0 right-0 px-3 text-slate-600 hover:text-slate-800';
    setPasswordToggleIcon(button, false);

    button.addEventListener('click', () => {
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        setPasswordToggleIcon(button, isHidden);
    });

    wrapper.appendChild(button);
    input.setAttribute(PASSWORD_TOGGLE_ATTR, 'true');
};

const initializePasswordToggles = (root = document) => {
    const passwordInputs = root.querySelectorAll('input[type="password"]');
    passwordInputs.forEach((input) => enhancePasswordField(input));
};

document.addEventListener('DOMContentLoaded', () => {
    initializeModalFieldIndicators();
    initializePasswordToggles();
});

window.addEventListener('open-modal', () => {
    initializePasswordToggles();
});

Alpine.start();
