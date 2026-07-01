{{--
    Live search helpers. Two modes:

    1) Client-side filter (no network) — for fully-loaded/unpaginated lists:
         <input data-live-filter data-live-no-submit data-filter-target="#some-tbody">
         <tbody id="some-tbody">
             <tr data-row data-search="lowercased text">...</tr>
             <tr data-empty hidden><td ...>No results match your search.</td></tr>
         </tbody>

    2) AJAX (server) search — for paginated lists, searches ALL pages without a
       full reload. The input must live OUTSIDE the swapped region so it keeps focus:
         <input data-live-search="#some-results">
         <div id="some-results"> ...table + pagination + row modals... </div>
       The current URL's other params (per_page, subdivision_id, …) are preserved;
       `page` is reset on each new query.
--}}
<script>
    (function () {
        // ---- Mode 1: client-side row filter -------------------------------------
        const wireFilter = (input) => {
            const target = document.querySelector(input.getAttribute('data-filter-target'));
            if (!target) {
                return;
            }

            const apply = () => {
                const query = input.value.trim().toLowerCase();
                const rows = target.querySelectorAll('[data-row]');
                let shown = 0;

                rows.forEach((row) => {
                    const haystack = (row.getAttribute('data-search') || row.textContent || '').toLowerCase();
                    const match = query === '' || haystack.indexOf(query) !== -1;
                    row.hidden = !match;
                    if (match) {
                        shown++;
                    }
                });

                const empty = target.querySelector('[data-empty]');
                if (empty) {
                    empty.hidden = !(rows.length > 0 && shown === 0);
                }
            };

            if (input.hasAttribute('data-live-no-submit')) {
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                    }
                });
            }
            input.addEventListener('input', apply);
            apply();
        };

        // ---- Mode 2: AJAX server search -----------------------------------------
        // Delegated so it keeps working even when the input itself sits inside the
        // swapped region and gets replaced. Focus + caret are restored after a swap.
        let timer = null;
        let controller = null;

        const runRemote = async (input) => {
            const form = input.closest('form');
            // data-live-search may list several regions (comma-separated) to swap from
            // one response — e.g. the table region AND a separate row-modals region.
            const targets = input.getAttribute('data-live-search')
                .split(',')
                .map((selector) => document.querySelector(selector.trim()))
                .filter((element) => element && element.id);
            if (!form || targets.length === 0) {
                return;
            }

            // Start from the current URL params, then layer this form's fields on top
            // so unrelated filters (per_page, subdivision_id, …) survive.
            const params = new URLSearchParams(window.location.search);
            new FormData(form).forEach((value, key) => {
                const v = typeof value === 'string' ? value.trim() : value;
                if (v === '' || v == null) {
                    params.delete(key);
                } else {
                    params.set(key, v);
                }
            });
            params.delete('page'); // a new query always starts at page 1

            const qs = params.toString();
            const url = form.getAttribute('action') + (qs ? '?' + qs : '');
            const refindSelector = 'input[data-live-search][name="' + input.getAttribute('name') + '"]';
            const typedValue = input.value;

            if (controller) {
                controller.abort();
            }
            controller = new AbortController();
            targets.forEach((target) => { target.style.opacity = '0.5'; });

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                targets.forEach((target) => {
                    const fresh = doc.getElementById(target.id);
                    if (fresh) {
                        target.innerHTML = fresh.innerHTML;
                    }
                });
                window.history.replaceState({}, '', url);

                // If the input lived inside a swapped region it was replaced; restore
                // focus, the user's latest text, and the caret at the end.
                if (!document.contains(input)) {
                    const replacement = document.querySelector(refindSelector);
                    if (replacement) {
                        if (replacement.value !== typedValue) {
                            replacement.value = typedValue;
                        }
                        replacement.focus();
                        try {
                            replacement.setSelectionRange(typedValue.length, typedValue.length);
                        } catch (error) { /* not all input types support selection */ }
                    }
                }
            } catch (error) {
                // Network/abort errors: leave the current results in place.
            } finally {
                targets.forEach((target) => { target.style.opacity = ''; });
            }
        };

        const matchesRemote = (el) => el && el.matches && el.matches('input[data-live-search]');

        document.addEventListener('input', (event) => {
            if (!matchesRemote(event.target)) {
                return;
            }
            const input = event.target;
            clearTimeout(timer);
            timer = setTimeout(() => runRemote(input), 300);
        });

        document.addEventListener('keydown', (event) => {
            if (!matchesRemote(event.target) || event.key !== 'Enter') {
                return;
            }
            event.preventDefault();
            clearTimeout(timer);
            runRemote(event.target);
        });

        const init = () => {
            document.querySelectorAll('input[data-live-filter]').forEach(wireFilter);
        };

        if (document.readyState !== 'loading') {
            init();
        } else {
            document.addEventListener('DOMContentLoaded', init);
        }
    })();
</script>
