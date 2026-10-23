export const isFamily = relationship => ['husband', 'wife', 'child', 'relative'].includes(relationship.trim().toLowerCase());

export function ownershipWarning(name, relationship) {
    const family = isFamily(relationship);
    return `Selected new owner: ${name} (${relationship}). ` + (family
        ? ''
        : 'This relationship is not listed as family. Check whether a spouse or family member was intended before assigning ownership.');
}

if (typeof document !== 'undefined') {
    const selector = 'select[data-ownership-successor]';
    const approved = new WeakSet();
    const pending = new WeakSet();
    function showSelection(select) {
        let note = select.parentElement.querySelector('[data-ownership-warning]');
        if (!note) {
            note = document.createElement('p');
            note.dataset.ownershipWarning = '';
            note.setAttribute('role', 'alert');
            note.className = 'mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900';
            select.insertAdjacentElement('afterend', note);
        }
        const option = select.selectedOptions[0];
        note.hidden = !select.value || isFamily(option.dataset.relationship);
        note.textContent = select.value ? ownershipWarning(option.dataset.name, option.dataset.relationship) : '';
    }
    document.addEventListener('change', event => {
        if (!event.target.matches?.(selector)) return;
        // A previous confirmation must never carry over to a different person.
        const confirmation = event.target.form?.querySelector('[name="confirm_transfer"]');
        if (confirmation) confirmation.checked = false;
        showSelection(event.target);
    });
    document.addEventListener('focusin', event => {
        if (event.target.matches?.(selector)) showSelection(event.target);
    });
    document.addEventListener('submit', event => {
        const form = event.target;
        if (approved.delete(form)) return;
        const select = form.querySelector(`${selector}:not(:disabled)`);
        if (!select?.value) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (pending.has(form)) return;
        pending.add(form);
        const submitter = event.submitter;
        const selectedValue = select.value;
        const option = select.selectedOptions[0];
        const family = isFamily(option.dataset.relationship);
        const dialog = document.createElement('dialog');
        dialog.className = 'ownership-dialog';
        dialog.setAttribute('aria-labelledby', 'ownership-dialog-title');
        dialog.setAttribute('aria-describedby', 'ownership-dialog-description');
        dialog.innerHTML = `<div class="ownership-dialog-body">
            <div class="ownership-dialog-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-7h6v7"/></svg></div>
            <h2 id="ownership-dialog-title">Confirm new owner</h2>
            <p id="ownership-dialog-description">Assign this resident as the new house owner?</p>
            <div class="ownership-dialog-person"><strong></strong><span></span></div>
            <p class="ownership-dialog-warning" hidden>This relationship is not listed as family. Check whether a spouse or family member was intended before confirming.</p>
            <div class="ownership-dialog-actions"><button type="button" data-cancel autofocus>Cancel</button><button type="button" data-confirm>Confirm Assignment</button></div>
        </div>`;
        dialog.querySelector('strong').textContent = option.dataset.name;
        dialog.querySelector('.ownership-dialog-person span').textContent = option.dataset.relationship;
        dialog.querySelector('.ownership-dialog-warning').hidden = family;
        document.body.append(dialog);
        let confirmed = false;
        dialog.addEventListener('keydown', event => event.stopPropagation());
        dialog.addEventListener('cancel', event => { event.preventDefault(); dialog.close(); });
        dialog.querySelector('[data-cancel]').onclick = () => dialog.close();
        dialog.querySelector('[data-confirm]').onclick = () => { confirmed = true; dialog.close(); };
        dialog.addEventListener('close', () => {
            pending.delete(form);
            dialog.remove();
            if (!confirmed) {
                const confirmation = form.querySelector('[name="confirm_transfer"]');
                if (confirmation) confirmation.checked = false;
                select.focus();
                return;
            }
            if (!form.isConnected || select.disabled) return;
            // Reconfirm if a background update changed the selected person.
            if (select.value === selectedValue) approved.add(form);
            try {
                form.requestSubmit(submitter?.isConnected && submitter.form === form ? submitter : undefined);
            } finally {
                approved.delete(form);
            }
        }, { once: true });
        dialog.showModal();
    }, true);
}
