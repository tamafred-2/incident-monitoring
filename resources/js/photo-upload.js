const MB = 1024 * 1024;

export function photoError(files, { extensions, maxMb, maxFiles, maxRequestBytes }) {
    const names = extensions.map(extension => extension.toUpperCase()).join(', ');
    if (files.some(file => !extensions.includes(file.name.split('.').pop().toLowerCase()) || !file.type.startsWith('image/'))) {
        return `Please select a ${names} photo only. Videos and other files are not allowed.`;
    }
    const tooLarge = files.find(file => file.size > maxMb * MB);
    if (tooLarge) {
        return `"${tooLarge.name}" is ${(tooLarge.size / MB).toFixed(1)} MB. Each photo must be ${maxMb} MB or smaller.`;
    }
    if (maxFiles && files.length > maxFiles) return `Select at most ${maxFiles} photos.`;
    const total = files.reduce((sum, file) => sum + file.size, 0);
    if (maxRequestBytes && total > maxRequestBytes) {
        return `These photos total ${(total / MB).toFixed(1)} MB, but at most ${Math.floor(maxRequestBytes / MB)} MB can be uploaded at once. Save fewer photos now and add the rest after.`;
    }

    return '';
}

if (typeof document !== 'undefined') {
    const selector = 'input[type="file"][data-photo-upload]';
    // Leave room for the other form fields sent with the photos.
    const maxRequestBytes = () => Math.max(0, Number(document.querySelector('meta[name="upload-max-bytes"]')?.content || 0) - MB);
    const options = input => ({
        extensions: input.accept.split(',').map(type => type.trim()).filter(type => type.startsWith('.')).map(type => type.slice(1).toLowerCase()),
        maxMb: Number(input.dataset.maxMb || 10),
        maxFiles: Number(input.dataset.maxFiles || 0),
        maxRequestBytes: maxRequestBytes(),
    });
    function showError(input, message) {
        let note = input.parentElement.querySelector('[data-photo-upload-error]');
        if (!note) {
            note = document.createElement('p');
            note.dataset.photoUploadError = '';
            note.setAttribute('role', 'alert');
            note.className = 'mt-1 text-sm text-rose-600';
            input.insertAdjacentElement('afterend', note);
        }
        note.hidden = !message;
        note.textContent = message;
        input.setCustomValidity(message);
    }
    function validate(input) {
        const message = input.disabled ? '' : photoError([...input.files], options(input));
        showError(input, message);
        return message;
    }
    document.addEventListener('change', event => {
        if (!event.target.matches?.(selector)) return;
        // Clears a combined-size error left on a sibling field.
        event.target.form?.querySelectorAll(selector).forEach(input => input !== event.target && validate(input));
        if (validate(event.target)) event.target.reportValidity();
    });
    // Runs before the other submit handlers so an oversized upload never leaves the browser.
    document.addEventListener('submit', event => {
        const form = event.target;
        const inputs = [...form.querySelectorAll(selector)];
        if (!inputs.length) return;
        let invalid = inputs.find(validate);
        const active = inputs.filter(input => !input.disabled && input.files.length);
        const total = active.reduce((sum, input) => sum + [...input.files].reduce((size, file) => size + file.size, 0), 0);
        if (!invalid && maxRequestBytes() && total > maxRequestBytes()) {
            invalid = active[0];
            showError(invalid, `These photos total ${(total / MB).toFixed(1)} MB, but at most ${Math.floor(maxRequestBytes() / MB)} MB can be uploaded at once. Save fewer photos now and add the rest after.`);
        }
        if (!invalid) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        invalid.reportValidity();
        invalid.focus();
    }, true);
    document.addEventListener('reset', event => {
        setTimeout(() => event.target.querySelectorAll(selector).forEach(input => showError(input, '')));
    });
}
