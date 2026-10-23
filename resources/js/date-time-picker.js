import '../../resources/css/date-time-picker.css';

const pad = n => String(n).padStart(2, '0');
export const dateKey = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
export function dateTimeValue(day, hour, minute, period, second = null) {
    return `${day}T${pad(Number(hour) % 12 + (period === 'PM' ? 12 : 0))}:${pad(minute)}${second === null ? '' : ':' + pad(second)}`;
}

if (typeof document !== 'undefined') {
    let active = null;
    const enhanced = new WeakSet();
    function enhance(input) {
        if (enhanced.has(input)) return;
        enhanced.add(input);
        const wrapper = document.createElement('div');
        wrapper.className = 'datetime-field';
        input.before(wrapper);
        wrapper.append(input);
        input.classList.add('datetime-editable');
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'datetime-trigger';
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-expanded', 'false');
        const label = input.labels?.[0]?.textContent.trim() || input.name.replaceAll('_', ' ');
        trigger.setAttribute('aria-label', `Open calendar for ${label}`);
        input.insertAdjacentElement('afterend', trigger);
        function sync() {
            trigger.disabled = input.disabled;
            if (input.disabled && active?.input === input) active.close(false);
        }
        // Let users finish typing every segment before auto-applying a filter.
        const autoApply = input.onchange;
        if (autoApply) {
            input.onchange = null;
            let pending = false;
            input.addEventListener('change', event => {
                if (event.isTrusted) pending = true;
                else { pending = false; autoApply.call(input, event); }
            });
            input.addEventListener('blur', event => {
                if (pending && event.relatedTarget !== trigger && input.validity.valid) {
                    pending = false;
                    autoApply.call(input, event);
                }
            });
        }
        sync();
        new MutationObserver(sync).observe(input, { attributes: true, attributeFilter: ['disabled', 'value', 'required', 'min', 'max'] });
        input.addEventListener('input', sync);
        input.addEventListener('change', sync);
        input.form?.addEventListener('reset', () => setTimeout(sync));
        trigger.addEventListener('click', () => {
            if (active?.input === input) { active.close(); return; }
            active?.close(false);
            if (input.disabled) return;
            const initial = input.value ? new Date(input.value) : new Date();
            let day = dateKey(initial), month = new Date(initial.getFullYear(), initial.getMonth(), 1);
            let hour = pad(initial.getHours() % 12 || 12), minute = pad(initial.getMinutes());
            let period = initial.getHours() >= 12 ? 'PM' : 'AM', second = pad(initial.getSeconds());
            const seconds = input.step === '1';
            const popup = document.createElement('div');
            popup.className = 'datetime-popup';
            popup.setAttribute('role', 'dialog');
            popup.setAttribute('aria-label', label);
            popup.tabIndex = -1;
            popup.innerHTML = `<div class="datetime-panels"><div class="datetime-calendar"><div class="datetime-heading"><strong aria-live="polite"></strong><button type="button" data-action="previous" aria-label="Previous month">&#8593;</button><button type="button" data-action="next" aria-label="Next month">&#8595;</button></div><div class="datetime-days"></div></div><div class="datetime-time" aria-label="Time"></div></div><p class="datetime-error" role="alert" hidden></p><div class="datetime-footer"><button type="button" data-action="clear">Clear</button><button type="button" data-action="today">Today</button><button type="button" data-action="okay">Okay</button></div>`;
            document.body.append(popup);
            trigger.setAttribute('aria-expanded', 'true');
            const error = popup.querySelector('.datetime-error');
            function position() {
                const rect = input.getBoundingClientRect();
                popup.style.left = `${Math.max(8, Math.min(rect.left, innerWidth - popup.offsetWidth - 8))}px`;
                popup.style.top = `${Math.max(8, Math.min(rect.bottom + 5, innerHeight - popup.offsetHeight - 8))}px`;
            }
            function close(focus = true) {
                popup.remove();
                trigger.setAttribute('aria-expanded', 'false');
                document.removeEventListener('pointerdown', outside, true);
                window.removeEventListener('resize', position);
                window.removeEventListener('scroll', position, true);
                active = null;
                if (focus) trigger.focus();
            }
            function outside(event) { if (!popup.contains(event.target) && !trigger.contains(event.target)) close(false); }
            active = { input, close };
            document.addEventListener('pointerdown', outside, true);
            window.addEventListener('resize', position);
            window.addEventListener('scroll', position, true);
            function calendar() {
                popup.querySelector('strong').textContent = month.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                const grid = popup.querySelector('.datetime-days');
                grid.replaceChildren();
                for (const name of ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']) {
                    const span = document.createElement('span'); span.textContent = name; grid.append(span);
                }
                for (let i = 0; i < 42; i++) {
                    const date = new Date(month.getFullYear(), month.getMonth(), i - month.getDay() + 1);
                    const key = dateKey(date), button = document.createElement('button');
                    button.type = 'button'; button.textContent = date.getDate();
                    button.setAttribute('aria-label', key);
                    button.setAttribute('aria-pressed', String(key === day));
                    button.classList.toggle('muted', date.getMonth() !== month.getMonth());
                    button.disabled = Boolean((input.min && key < input.min.slice(0, 10)) || (input.max && key > input.max.slice(0, 10)));
                    button.onclick = () => { day = key; error.hidden = true; calendar(); grid.querySelector(`[aria-label="${key}"]`)?.focus(); };
                    grid.append(button);
                }
            }
            function column(label, options, selected, update) {
                const list = document.createElement('div'); list.className = 'datetime-column';
                list.setAttribute('role', 'group'); list.setAttribute('aria-label', label);
                for (const value of options) {
                    const button = document.createElement('button'); button.type = 'button'; button.textContent = value;
                    button.setAttribute('aria-label', `${label} ${value}`);
                    button.setAttribute('aria-pressed', String(value === selected));
                    button.onclick = () => { update(value); error.hidden = true; list.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', String(b === button))); };
                    list.append(button);
                }
                popup.querySelector('.datetime-time').append(list);
                list.scrollTop = options.indexOf(selected) * 36;
            }
            calendar();
            column('Hour', Array.from({ length: 12 }, (_, i) => pad(i + 1)), hour, v => hour = v);
            column('Minute', Array.from({ length: 60 }, (_, i) => pad(i)), minute, v => minute = v);
            if (seconds) column('Second', Array.from({ length: 60 }, (_, i) => pad(i)), second, v => second = v);
            column('Period', ['AM', 'PM'], period, v => period = v);
            popup.addEventListener('click', event => {
                const action = event.target.closest('[data-action]')?.dataset.action;
                if (action === 'previous' || action === 'next') { month = new Date(month.getFullYear(), month.getMonth() + (action === 'next' ? 1 : -1), 1); calendar(); }
                if (action === 'today') { const now = new Date(); day = dateKey(now); month = new Date(now.getFullYear(), now.getMonth(), 1); calendar(); error.hidden = true; }
                if (action === 'okay' || action === 'clear') {
                    const old = input.value;
                    input.value = action === 'clear' ? '' : dateTimeValue(day, hour, minute, period, seconds ? second : null);
                    if (action === 'okay' && !input.validity.valid) {
                        error.textContent = input.validationMessage; error.hidden = false; input.value = old; position(); return;
                    }
                    trigger.removeAttribute('aria-invalid');
                    close(); sync();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            popup.addEventListener('keydown', event => {
                if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); close(); }
                if (event.key === 'Tab') {
                    const buttons = [...popup.querySelectorAll('button:not(:disabled)')];
                    if (event.shiftKey && (document.activeElement === buttons[0] || document.activeElement === popup)) { event.preventDefault(); buttons.at(-1).focus(); }
                    else if (!event.shiftKey && document.activeElement === buttons.at(-1)) { event.preventDefault(); buttons[0].focus(); }
                }
            });
            position(); popup.focus();
        });
    }
    function scan() { document.querySelectorAll('input[type="datetime-local"]').forEach(enhance); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
    new MutationObserver(records => { if (records.some(record => [...record.addedNodes].some(node => node.nodeType === 1 && (node.matches?.('input[type="datetime-local"]') || node.querySelector?.('input[type="datetime-local"]'))))) scan(); }).observe(document.documentElement, { childList: true, subtree: true });
}
