export function appendBatch(container, html, keyAttribute) {
    if (!html) return 0;
    const template = document.createElement('template');
    const isTable = container.tagName === 'TBODY';
    template.innerHTML = isTable ? `<table><tbody>${html}</tbody></table>` : html;
    const source = isTable ? template.content.querySelector('tbody') : template.content;
    const existing = new Set(Array.from(container.querySelectorAll(`[${keyAttribute}]`), (node) => node.getAttribute(keyAttribute)));
    let added = 0;
    for (const node of Array.from(source.children)) {
        const key = node.getAttribute(keyAttribute);
        // Empty states and local-filter placeholders are not additional records.
        if (key === null || existing.has(key)) continue;
        existing.add(key);
        container.appendChild(node);
        added++;
    }
    return added;
}

export function recordList({ name, nextUrl, loaded, modalTarget }) {
    return {
        name, nextUrl, loaded, modalTarget,
        busy: false, error: false, touchY: 0,
        requestController: null, resizeObserver: null, resizeHandler: null,
        init() {
            this.$nextTick(() => {
                this.resizeHandler = () => { this.fitAvailableSpace(); this.fillVisibleSpace(); };
                this.resizeObserver = new ResizeObserver(this.resizeHandler);
                this.resizeObserver.observe(this.$root);
                window.addEventListener('resize', this.resizeHandler);
                window.addEventListener('scroll', this.resizeHandler, { passive: true });
                window.visualViewport?.addEventListener('resize', this.resizeHandler);
                this.resizeHandler();
            });
        },
        destroy() {
            this.requestController?.abort();
            this.resizeObserver?.disconnect();
            window.removeEventListener('resize', this.resizeHandler);
            window.removeEventListener('scroll', this.resizeHandler);
            window.visualViewport?.removeEventListener('resize', this.resizeHandler);
        },
        visible() {
            const rect = this.$refs.viewport.getBoundingClientRect();
            return this.$root.getClientRects().length > 0 && rect.bottom > 0 && rect.top < window.innerHeight;
        },
        fitAvailableSpace() {
            if (!this.visible()) return;
            const viewport = this.$refs.viewport;
            const top = Math.max(96, viewport.getBoundingClientRect().top);
            let height = Math.max(200, Math.floor((window.visualViewport?.height ?? window.innerHeight)
                - top - this.$refs.footer.offsetHeight - 24));
            const content = viewport.querySelector?.('table') ?? viewport.querySelector?.('[data-scroll-rows]');
            if (!this.nextUrl && content) {
                height = Math.min(height, Math.ceil(content.getBoundingClientRect().height));
            }
            if (viewport.style.height !== `${height}px`) viewport.style.height = `${height}px`;
        },
        message() {
            if (this.busy) return 'Loading more…';
            if (this.error) return 'Could not load more records.';
            return `${this.loaded} loaded · ${this.nextUrl ? 'Scroll for more' : 'All records loaded'}`;
        },
        async fillVisibleSpace() {
            const viewport = this.$refs.viewport;
            while (this.visible() && this.nextUrl && !this.busy && !this.error
                && viewport.scrollHeight <= viewport.clientHeight) {
                const previousUrl = this.nextUrl;
                await this.loadMore();
                await this.$nextTick();
                if (this.nextUrl === previousUrl) break;
            }
        },
        onScroll() {
            const viewport = this.$refs.viewport;
            if (!this.error && viewport.scrollHeight - viewport.scrollTop - viewport.clientHeight <= 64) {
                this.loadMore();
            }
        },
        appendRows(html) {
            const rows = this.$root.querySelector('[data-scroll-rows]');
            return appendBatch(rows, html, 'data-record-key');
        },
        appendModals(html) {
            if (!html || !this.modalTarget) return;
            const target = document.querySelector(this.modalTarget);
            if (target) appendBatch(target, html, 'data-record-modal');
        },
        async loadMore() {
            if (this.busy || !this.nextUrl) return;
            this.busy = true;
            this.error = false;
            this.requestController = new AbortController();
            try {
                const url = new URL(this.nextUrl, window.location.origin);
                if (url.origin !== window.location.origin) throw new Error('Invalid list URL');
                const response = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-Scroll-List': this.name },
                    credentials: 'same-origin', signal: this.requestController.signal,
                });
                if (!response.ok) throw new Error('List request failed');
                const batch = await response.json();
                if (!this.$root.isConnected) return;
                if (batch.count) {
                    this.loaded += this.appendRows(batch.html);
                    this.appendModals(batch.modals);
                }
                this.nextUrl = batch.next_url;
                this.fitAvailableSpace();
            } catch (error) {
                if (error.name !== 'AbortError') this.error = true;
            } finally {
                this.busy = false;
            }
        },
    };
}

window.recordList = recordList;
