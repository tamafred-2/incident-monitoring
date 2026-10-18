export function tableViewportHeight({ viewportHeight, top, columns, tableCount, gap, chrome, bottom = 16 }) {
    const gridRows = Math.ceil(tableCount / columns);
    const available = viewportHeight - top - bottom - gap * (gridRows - 1);
    // Keep short/mobile screens usable; the page can scroll when both tables cannot fit.
    return Math.max(160, Math.floor(available / gridRows - chrome));
}

// Fetch only enough records to fill the viewport, then request more on scroll intent.
window.dashboardTable = ({ endpoint, cursor, cursorName, loaded, searchKey = 'visitor_q', query = '' }) => ({
    endpoint,
    cursor,
    cursorName,
    loaded,
    searchKey,
    query,
    searchPending: false,
    searchTimer: null,
    requestId: 0,
    controller: null,
    failedReset: false,
    busy: false,
    error: false,
    touchY: 0,
    resizeObserver: null,
    resizeHandler: null,
    init() {
        this.$nextTick(() => {
            this.resizeHandler = () => {
                this.fitAvailableSpace();
                this.fillVisibleSpace();
            };
            this.resizeObserver = new ResizeObserver(this.resizeHandler);
            this.resizeObserver.observe(this.$root.closest('main').parentElement);
            window.addEventListener('resize', this.resizeHandler);
            window.visualViewport?.addEventListener('resize', this.resizeHandler);
            this.resizeHandler();
        });
    },
    destroy() {
        clearTimeout(this.searchTimer);
        this.requestId++;
        this.controller?.abort();
        this.resizeObserver?.disconnect();
        window.removeEventListener('resize', this.resizeHandler);
        window.visualViewport?.removeEventListener('resize', this.resizeHandler);
    },
    scheduleSearch() {
        clearTimeout(this.searchTimer);
        this.requestId++;
        this.controller?.abort();
        this.busy = false;
        this.error = false;
        this.searchPending = true;
        this.searchTimer = setTimeout(() => this.reload(), 300);
    },
    async reload() {
        clearTimeout(this.searchTimer);
        this.requestId++;
        this.controller?.abort();
        this.busy = false;
        this.searchPending = false;
        this.error = false;
        this.cursor = null;
        this.loaded = 0;
        this.$refs.rows.innerHTML = '';
        this.$refs.viewport.scrollTop = 0;
        await this.loadMore(true);
        await this.$nextTick();
        await this.fillVisibleSpace();
    },
    retry() {
        return this.failedReset ? this.reload() : this.loadMore();
    },
    fitAvailableSpace() {
        const viewport = this.$refs.viewport;
        const grid = this.$root.closest('[data-dashboard-tables]');
        const style = window.getComputedStyle(grid);
        const columns = style.gridTemplateColumns.split(' ').filter(Boolean).length;
        const top = grid.getBoundingClientRect().top + window.scrollY;
        const chrome = viewport.getBoundingClientRect().top - this.$root.getBoundingClientRect().top
            + this.$refs.footer.offsetHeight + 1;
        const height = tableViewportHeight({
            viewportHeight: window.visualViewport?.height ?? window.innerHeight,
            top, columns, tableCount: grid.querySelectorAll('[data-dashboard-table]').length,
            gap: parseFloat(style.rowGap) || 0, chrome,
            bottom: parseFloat(style.getPropertyValue?.('--dashboard-bottom-space')) || 16,
        });
        if (viewport.style.height !== `${height}px`) viewport.style.height = `${height}px`;
    },
    async fillVisibleSpace() {
        const viewport = this.$refs.viewport;
        while (this.cursor && !this.busy && !this.error && !this.searchPending && viewport.scrollHeight <= viewport.clientHeight) {
            const previousCount = this.loaded;
            await this.loadMore();
            await this.$nextTick();
            if (this.loaded === previousCount) break;
        }
    },
    message() {
        if (this.searchPending) return 'Searching…';
        if (this.busy && !this.loaded) return 'Loading records…';
        if (this.busy) return 'Loading more…';
        if (this.error) return 'Could not load records.';
        if (!this.loaded && this.query.trim()) return 'No matching records.';
        if (!this.loaded) return 'No records to show.';
        return `${this.loaded} loaded · ${this.cursor ? 'Scroll for more' : 'All records loaded'}`;
    },
    onScroll() {
        const viewport = this.$refs.viewport;
        if (!this.error && !this.searchPending && viewport.scrollHeight - viewport.scrollTop - viewport.clientHeight <= 64) {
            this.loadMore();
        }
    },
    async loadMore(reset = false) {
        if (this.busy || this.searchPending || (!reset && !this.cursor)) return;
        this.busy = true;
        this.error = false;
        const requestId = ++this.requestId;
        this.controller = new AbortController();
        const url = new URL(this.endpoint, window.location.origin);
        url.searchParams.set(this.searchKey, this.query.trim());
        if (reset) url.searchParams.delete(this.cursorName);
        else url.searchParams.set(this.cursorName, this.cursor);
        try {
            const response = await fetch(url, {
                signal: this.controller.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Dashboard records request failed');
            const batch = await response.json();
            if (requestId !== this.requestId) return;
            if (reset) {
                this.$refs.rows.innerHTML = batch.html;
            } else if (batch.count) {
                this.$refs.rows.insertAdjacentHTML('beforeend', batch.html);
            }
            this.loaded += batch.count;
            this.cursor = batch.next_cursor;
            this.failedReset = false;
        } catch (error) {
            if (requestId !== this.requestId || error.name === 'AbortError') return;
            this.failedReset = reset;
            this.error = true;
        } finally {
            if (requestId === this.requestId) this.busy = false;
        }
    },
});
