import assert from 'node:assert/strict';
import { test } from 'node:test';

globalThis.window = {
    location: { origin: 'http://localhost' }, innerHeight: 619, scrollY: 0,
    getComputedStyle: () => ({ gridTemplateColumns: '480px 320px', rowGap: '12px' }),
    addEventListener() {}, removeEventListener() {},
};
globalThis.ResizeObserver = class {
    observe() {}
    disconnect() {}
};
const { tableViewportHeight } = await import('../../resources/js/dashboard-tables.js');

function table() {
    const state = window.dashboardTable({
        endpoint: '/dashboard/records/visitors', cursor: 'first', cursorName: 'visitor_cursor', loaded: 5,
    });
    state.$nextTick = (callback) => callback?.();
    const grid = {
        getBoundingClientRect: () => ({ top: 202 }),
        querySelectorAll: () => [{}, {}],
    };
    state.$root = {
        getBoundingClientRect: () => ({ top: 202 }),
        closest: (selector) => selector === 'main' ? { parentElement: {} } : grid,
    };
    state.$refs = {
        viewport: {
            scrollHeight: 321, scrollTop: 0, clientHeight: 320, style: {},
            getBoundingClientRect: () => ({ top: 250 }),
        },
        rows: { querySelectorAll: () => [], insertAdjacentHTML: () => {} },
        footer: { offsetHeight: 32 },
    };
    return state;
}

test('initialization and scrolling away from the bottom do not fetch records', () => {
    const state = table();
    globalThis.fetch = () => assert.fail('Unexpected eager request');
    state.init();
    state.$refs.viewport.scrollHeight = 1000;
    state.onScroll();
    state.destroy();
    assert.equal(state.loaded, 5);
});

test('scrolling at the bottom loads one batch and blocks overlapping requests', async () => {
    const state = table();
    let complete;
    let requests = 0;
    let appended = '';
    state.$refs.rows.insertAdjacentHTML = (position, html) => { appended += html; };
    globalThis.fetch = (url) => {
        requests++;
        assert.equal(url.searchParams.get('visitor_cursor'), 'first');
        return new Promise((resolve) => { complete = resolve; });
    };
    state.onScroll();
    state.onScroll();
    assert.equal(requests, 1);
    assert.equal(state.busy, true);
    complete({ ok: true, json: async () => ({ html: '<tr></tr>', count: 5, next_cursor: 'second' }) });
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(state.loaded, 10);
    assert.equal(state.cursor, 'second');
    assert.equal(state.busy, false);
    assert.equal(appended, '<tr></tr>');
    assert.equal(requests, 1, 'Appending records must not automatically fetch another batch');
});

test('failed requests preserve the cursor and allow an explicit retry', async () => {
    const state = table();
    globalThis.fetch = async () => ({ ok: false });
    await state.loadMore();
    assert.equal(state.error, true);
    assert.equal(state.cursor, 'first');
    assert.equal(state.loaded, 5);
    globalThis.fetch = async () => ({
        ok: true, json: async () => ({ html: '', count: 0, next_cursor: null }),
    });
    await state.loadMore();
    assert.equal(state.error, false);
    assert.equal(state.cursor, null);
    globalThis.fetch = () => assert.fail('Exhausted table must stop requesting');
    state.onScroll();
});

test('viewport follows available space instead of the number of loaded rows', () => {
    const state = table();
    state.fitAvailableSpace();
    assert.equal(state.$refs.viewport.style.height, '320px');
});

test('125 percent zoom reduces the viewport budget and stacked tables share it', () => {
    const layout = { top: 200, columns: 2, tableCount: 2, gap: 12, chrome: 80 };
    assert.equal(tableViewportHeight({ ...layout, viewportHeight: 768 }), 472);
    assert.equal(tableViewportHeight({ ...layout, viewportHeight: 614 }), 318);
    assert.equal(tableViewportHeight({ ...layout, columns: 1, viewportHeight: 800 }), 206);
    assert.equal(tableViewportHeight({ ...layout, columns: 1, viewportHeight: 400 }), 160);
});

test('larger viewports fetch only the batch needed to fill visible space', async () => {
    const state = table();
    state.$refs.viewport.clientHeight = 580;
    let requests = 0;
    state.$refs.rows.insertAdjacentHTML = () => { state.$refs.viewport.scrollHeight += 280; };
    globalThis.fetch = async () => {
        requests++;
        return { ok: true, json: async () => ({ html: '<tr></tr>', count: 5, next_cursor: 'second' }) };
    };
    await state.fillVisibleSpace();
    assert.equal(requests, 1);
    assert.equal(state.loaded, 10);
    assert.equal(state.cursor, 'second');
});

test('search resets the cursor and scrolling retains its query', async () => {
    const state = table();
    state.query = 'Ana';
    const requests = [];
    globalThis.fetch = async (url) => {
        requests.push(url);
        return { ok: true, json: async () => ({ html: '<tr>Ana</tr>', count: 1, next_cursor: 'filtered' }) };
    };
    await state.reload();
    assert.equal(requests[0].searchParams.has('visitor_cursor'), false);
    assert.equal(state.loaded, 1);
    assert.equal(state.$refs.rows.innerHTML, '<tr>Ana</tr>');
    await state.loadMore();
    assert.equal(requests[1].searchParams.get('visitor_q'), 'Ana');
    assert.equal(requests[1].searchParams.get('visitor_cursor'), 'filtered');
});

test('a stale scroll response cannot overwrite newer search results', async () => {
    const state = table();
    let completeOld;
    globalThis.fetch = () => new Promise((resolve) => { completeOld = resolve; });
    const pending = state.loadMore();
    state.query = 'New';
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ html: '<tr>New</tr>', count: 1, next_cursor: null }) });
    await state.reload();
    completeOld({ ok: true, json: async () => ({ html: '<tr>Old</tr>', count: 5, next_cursor: 'old' }) });
    await pending;
    assert.equal(state.loaded, 1);
    assert.equal(state.cursor, null);
    assert.equal(state.$refs.rows.innerHTML, '<tr>New</tr>');
});

test('a failed search can retry its first batch and clearing search removes the filter', async () => {
    const state = table();
    state.query = 'Missing';
    globalThis.fetch = async () => ({ ok: false });
    await state.reload();
    assert.equal(state.error, true);
    let request;
    globalThis.fetch = async (url) => {
        request = url;
        return { ok: true, json: async () => ({ html: '<tr>No matches</tr>', count: 0, next_cursor: null }) };
    };
    await state.retry();
    assert.equal(request.searchParams.has('visitor_cursor'), false);
    assert.equal(state.error, false);
    assert.equal(state.message(), 'No matching records.');
    state.query = '';
    await state.reload();
    assert.equal(request.searchParams.get('visitor_q'), '');
});
