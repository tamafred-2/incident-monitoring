import assert from 'node:assert/strict';
import { test } from 'node:test';

globalThis.window = {
    location: { origin: 'http://localhost' }, innerHeight: 800,
    addEventListener() {}, removeEventListener() {},
};
globalThis.ResizeObserver = class { observe() {} disconnect() {} };
const { recordList, appendBatch } = await import('../../resources/js/record-lists.js');

function list() {
    const state = recordList({
        name: 'residents', nextUrl: '/residents?q=Smith&status=Active&page=2', loaded: 10, modalTarget: '#dialogs',
    });
    state.$root = { isConnected: true, getClientRects: () => [{}] };
    state.$refs = {
        viewport: { scrollHeight: 1200, scrollTop: 0, clientHeight: 500, style: {}, getBoundingClientRect: () => ({ top: 240, bottom: 740 }) },
        footer: { offsetHeight: 36 },
    };
    state.$nextTick = (callback) => callback?.();
    state.appendRows = () => 10;
    state.appendModals = () => {};
    return state;
}

test('initialization fits available space without fetching beyond visible records', () => {
    const state = list();
    globalThis.fetch = () => assert.fail('Unexpected initial batch request');
    state.init();
    assert.equal(state.$refs.viewport.style.height, '500px');
    state.onScroll();
    state.destroy();
});

test('hidden tabs never fetch batches just to fill invisible space', async () => {
    const state = list();
    state.$root.getClientRects = () => [];
    state.$refs.viewport.scrollHeight = 100;
    globalThis.fetch = () => assert.fail('Hidden tab requested records');
    await state.fillVisibleSpace();
});

test('short completed lists shrink to their content instead of leaving a large blank table', () => {
    const state = list();
    state.nextUrl = null;
    state.$refs.viewport.querySelector = () => ({ getBoundingClientRect: () => ({ height: 140 }) });
    state.fitAvailableSpace();
    assert.equal(state.$refs.viewport.style.height, '140px');
});

test('scroll request preserves filters and loads only one batch at a time', async () => {
    const state = list();
    let complete;
    let requests = 0;
    let modals = '';
    state.appendModals = (html) => { modals = html; };
    globalThis.fetch = (url, options) => {
        requests++;
        assert.equal(url.searchParams.get('q'), 'Smith');
        assert.equal(url.searchParams.get('status'), 'Active');
        assert.equal(url.searchParams.get('page'), '2');
        assert.equal(options.headers['X-Scroll-List'], 'residents');
        return new Promise((resolve) => { complete = resolve; });
    };
    const pending = state.loadMore();
    await state.loadMore();
    assert.equal(requests, 1);
    complete({ ok: true, json: async () => ({ html: '<tr></tr>', modals: '<div>Dialog</div>', count: 10, next_url: '/residents?q=Smith&page=3' }) });
    await pending;
    assert.equal(state.loaded, 20);
    assert.equal(modals, '<div>Dialog</div>');
    assert.equal(state.nextUrl, '/residents?q=Smith&page=3');
});

test('failed requests keep their URL for retry and exhausted lists stop loading', async () => {
    const state = list();
    globalThis.fetch = async () => ({ ok: false });
    await state.loadMore();
    assert.equal(state.error, true);
    assert.match(state.nextUrl, /page=2/);
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ html: '', modals: '', count: 0, next_url: null }) });
    await state.loadMore();
    assert.equal(state.error, false);
    assert.equal(state.nextUrl, null);
    globalThis.fetch = () => assert.fail('Exhausted list requested more');
    await state.loadMore();
});

test('search replacement aborts the old request and never appends stale results', async () => {
    const state = list();
    let complete;
    let signal;
    globalThis.fetch = (url, options) => {
        signal = options.signal;
        return new Promise((resolve) => { complete = resolve; });
    };
    state.appendRows = () => assert.fail('Stale search result appended');
    const pending = state.loadMore();
    state.$root.isConnected = false;
    state.destroy();
    assert.equal(signal.aborted, true);
    complete({ ok: true, json: async () => ({ html: '<tr></tr>', count: 10, next_url: null }) });
    await pending;
    assert.equal(state.loaded, 10);
});

test('appending batches skips duplicated records and empty-state placeholders', () => {
    const node = (key) => ({ getAttribute: () => key });
    const nodes = [node('1'), node('2'), node(null), node('2'), node('3')];
    globalThis.document = {
        createElement: () => ({ content: { querySelector: () => ({ children: nodes }) } }),
    };
    const added = [];
    const target = { tagName: 'TBODY', querySelectorAll: () => [node('1')], appendChild: (item) => added.push(item.getAttribute()) };
    assert.equal(appendBatch(target, 'table rows', 'data-record-key'), 2);
    assert.deepEqual(added, ['2', '3']);
});
