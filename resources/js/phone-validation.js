export const phoneDigits = value => value.replace(/[^0-9]/g, '').slice(0, 11);
export const validPhone = value => /^[0-9]{11}$/.test(value);

if (typeof document !== 'undefined') {
    const selector = 'input[type="tel"]';
    function validate(input) {
        if (input.readOnly || input.disabled) return;
        input.inputMode = 'numeric';
        input.pattern = '[0-9]{11}';
        input.minLength = 11;
        input.maxLength = 11;
        input.title = 'Enter exactly 11 digits.';
        input.setCustomValidity(input.value && !validPhone(input.value) ? 'Enter exactly 11 digits. Numbers only.' : '');
    }
    // Delegation also covers forms inserted by modals and live lists.
    document.addEventListener('input', event => {
        const input = event.target;
        if (!input.matches?.(selector) || input.readOnly || input.disabled) return;
        const position = input.selectionStart;
        const beforeCursor = input.value.slice(0, position ?? input.value.length);
        const cleaned = phoneDigits(input.value);
        if (input.value !== cleaned) {
            input.value = cleaned;
            const caret = Math.min(phoneDigits(beforeCursor).length, cleaned.length);
            input.setSelectionRange(caret, caret);
        }
        validate(input);
    }, true);
    document.addEventListener('focusin', event => {
        if (event.target.matches?.(selector)) validate(event.target);
    });
    document.addEventListener('reset', event => {
        setTimeout(() => event.target.querySelectorAll(selector).forEach(validate));
    });
}
