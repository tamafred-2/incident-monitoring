const submitting = new WeakSet();

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.matches?.('[data-visitor-check-in]')) return;
    event.preventDefault();
    if (submitting.has(form)) return;
    submitting.add(form);
    const errors = form.querySelector('[data-check-in-errors]');
    const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type])')];
    const previous = buttons.map(button => ({ button, text: button.textContent, disabled: button.disabled }));
    const body = new FormData(form);
    errors.hidden = true;
    errors.textContent = '';
    form.setAttribute('aria-busy', 'true');
    buttons.forEach(button => { button.disabled = true; button.textContent = 'Checking in…'; });
    try {
        const response = await fetch(form.action, {
            method: 'POST', body, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = response.headers.get('content-type')?.includes('application/json') ? await response.json() : {};
        if (!response.ok || !data.saved || !data.redirect_url) {
            const messages = Object.values(data.errors || {}).flat();
            throw new Error(messages.length ? messages.join('\n') : (data.message || 'Check-in could not be confirmed. Please try again.'));
        }
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'visitor-check-in' }));
        window.location.assign(data.redirect_url);
    } catch (error) {
        errors.textContent = error.message || 'Unable to connect. Please try again.';
        errors.style.whiteSpace = 'pre-line';
        errors.hidden = false;
        errors.focus();
    } finally {
        submitting.delete(form);
        form.removeAttribute('aria-busy');
        previous.forEach(({ button, text, disabled }) => { button.disabled = disabled; button.textContent = text; });
    }
});
