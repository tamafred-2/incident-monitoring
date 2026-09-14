// A token belongs to one form instance, so intentionally starting another
// form remains possible even when its fields match a previous submission.
const pendingForms = new Set();

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') return;
    if (pendingForms.has(form)) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return;
    }
    if (!form.querySelector('[name="_submission_token"]')) {
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_submission_token';
        token.value = Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join('');
        form.appendChild(token);
    }
    pendingForms.add(form);
    // Let existing form validators cancel first, without locking retries.
    queueMicrotask(() => {
        if (event.defaultPrevented) {
            pendingForms.delete(form);
            return;
        }
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach(button => {
            button.setAttribute('aria-disabled', 'true');
            button.classList.add('opacity-60', 'cursor-wait');
        });
    });
}, true);

window.addEventListener('pageshow', () => {
    pendingForms.forEach(form => {
        form.removeAttribute('aria-busy');
        form.querySelectorAll('[aria-disabled="true"]').forEach(button => {
            button.removeAttribute('aria-disabled');
            button.classList.remove('opacity-60', 'cursor-wait');
        });
    });
    pendingForms.clear();
});
