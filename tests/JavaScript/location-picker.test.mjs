import test from 'node:test';
import assert from 'node:assert/strict';
import { setupLocationGps } from '../../resources/js/location-picker.js';

function fixture({ secure = true, geolocation = true, fetcher = async () => ({ ok: true, json: async () => ({ locality: 'Phường Bến Nghé', city: 'Hồ Chí Minh', principalSubdivision: 'Hồ Chí Minh', countryName: 'Việt Nam' }) }) } = {}) {
    const handlers = {}, positions = [], requests = [], selected = [], timers = new Map();
    const button = { disabled: false, attributes: {}, setAttribute(name, value) { this.attributes[name] = value; }, addEventListener(name, callback) { handlers[name] = callback; } };
    const status = { textContent: '' };
    const modal = {
        addEventListener(name, callback) { handlers[name] = callback; },
        dispatchEvent(event) { selected.push(event.detail.name); },
    };
    const elements = { locationPickerModal: modal, btnLocationGPS: button, locationPickerStatus: status };
    const win = {
        isSecureContext: secure,
        navigator: { geolocation: geolocation ? { getCurrentPosition(resolve, reject, options) { positions.push({ resolve, reject, options }); } } : undefined },
        AbortController,
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
        setTimeout(callback) { const id = Symbol(); timers.set(id, callback); return id; },
        clearTimeout(id) { timers.delete(id); },
        fetch(url, options) { requests.push({ url: new URL(url), options }); return fetcher(url, options); },
    };
    setupLocationGps({ getElementById: id => elements[id] }, win);
    return { button, status, positions, requests, selected, timers, click: () => handlers.click(), hide: () => handlers['hide.bs.modal']() };
}

const nextTurn = () => new Promise(resolve => setImmediate(resolve));
const currentPosition = { coords: { latitude: 10.7769, longitude: 106.7009 } };

test('GPS requests current coordinates only after clicking, then selects a real deduplicated area', async () => {
    const ui = fixture();
    assert.equal(ui.positions.length, 0);
    assert.equal(ui.requests.length, 0);
    const pending = ui.click();
    assert.equal(ui.button.disabled, true);
    assert.deepEqual(ui.positions[0].options, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    ui.positions[0].resolve(currentPosition);
    await pending;
    assert.deepEqual(ui.selected, ['Phường Bến Nghé, Hồ Chí Minh, Việt Nam']);
    const { url, options } = ui.requests[0];
    assert.equal(url.origin + url.pathname, 'https://api.bigdatacloud.net/data/reverse-geocode-client');
    assert.equal(url.searchParams.get('latitude'), '10.7769');
    assert.equal(url.searchParams.get('longitude'), '106.7009');
    assert.equal(url.searchParams.get('localityLanguage'), 'vi');
    assert.equal(options.credentials, 'omit');
    assert.equal(options.referrerPolicy, 'no-referrer');
    assert.equal(ui.button.disabled, false);
    assert.equal(ui.button.attributes['aria-busy'], 'false');
    assert.equal(ui.timers.size, 0);
});

for (const [code, message] of [[1, /chưa cho phép/], [2, /bật dịch vụ vị trí/], [3, /quá lâu/]]) {
    test(`GPS error ${code} explains the problem and never substitutes a city`, async () => {
        const ui = fixture();
        const pending = ui.click();
        ui.positions[0].reject({ code });
        await pending;
        assert.match(ui.status.textContent, message);
        assert.equal(ui.button.disabled, false);
        assert.equal(ui.requests.length, 0);
        assert.deepEqual(ui.selected, []);
    });
}

for (const [options, message] of [[{ secure: false }, /HTTPS/], [{ geolocation: false }, /không hỗ trợ/]]) {
    test(`unsupported GPS environment shows ${message} without a request`, async () => {
        const ui = fixture(options);
        await ui.click();
        assert.match(ui.status.textContent, message);
        assert.equal(ui.positions.length, 0);
        assert.equal(ui.requests.length, 0);
        assert.deepEqual(ui.selected, []);
    });
}

for (const fetcher of [
    async () => { throw new Error('Failed to fetch'); },
    async () => ({ ok: false }),
    async () => ({ ok: true, json: async () => { throw new SyntaxError(); } }),
    async () => ({ ok: true, json: async () => null }),
    async () => ({ ok: true, json: async () => ({ countryName: 'Việt Nam' }) }),
    async () => ({ ok: true, json: async () => ({ error: 'Provider error', city: 'Wrong city' }) }),
]) {
    test('failed or incomplete lookup preserves the existing selection and allows retry', async () => {
        const ui = fixture({ fetcher });
        const pending = ui.click();
        ui.positions[0].resolve(currentPosition);
        await pending;
        assert.match(ui.status.textContent, /Không tra được tên khu vực/);
        assert.deepEqual(ui.selected, []);
        assert.equal(ui.button.disabled, false);
        assert.equal(ui.timers.size, 0);
        const retry = ui.click();
        assert.equal(ui.positions.length, 2);
        ui.positions[1].reject({ code: 1 });
        await retry;
    });
}

test('invalid coordinates never reach the provider or trigger IP fallback', async () => {
    for (const coords of [{ latitude: NaN, longitude: 1 }, { latitude: 91, longitude: 1 }, { latitude: 1, longitude: -181 }]) {
        const ui = fixture();
        const pending = ui.click();
        ui.positions[0].resolve({ coords });
        await pending;
        assert.equal(ui.requests.length, 0);
        assert.deepEqual(ui.selected, []);
        assert.match(ui.status.textContent, /Không lấy được tọa độ/);
    }
});

test('duplicate clicks request GPS once and closing ignores a late GPS response', async () => {
    const ui = fixture();
    const pending = ui.click();
    await ui.click();
    assert.equal(ui.positions.length, 1);
    ui.hide();
    assert.equal(ui.button.disabled, false);
    ui.positions[0].resolve(currentPosition);
    await pending;
    assert.equal(ui.requests.length, 0);
    assert.deepEqual(ui.selected, []);
});

test('closing during lookup aborts it and stale results cannot overwrite a newer choice', async () => {
    let finish;
    const ui = fixture({ fetcher: () => new Promise(resolve => { finish = resolve; }) });
    const pending = ui.click();
    ui.positions[0].resolve(currentPosition);
    await nextTurn();
    ui.hide();
    assert.equal(ui.requests[0].options.signal.aborted, true);
    const retry = ui.click();
    finish({ ok: true, json: async () => ({ city: 'Stale city' }) });
    await pending;
    assert.deepEqual(ui.selected, []);
    assert.equal(ui.button.disabled, true);
    ui.positions[1].reject({ code: 1 });
    await retry;
});

test('lookup timeout aborts the network request, preserves selection and releases the button', async () => {
    const ui = fixture({ fetcher: (url, { signal }) => new Promise((resolve, reject) => {
        signal.addEventListener('abort', () => reject(new DOMException('Timed out', 'AbortError')));
    }) });
    const pending = ui.click();
    ui.positions[0].resolve(currentPosition);
    await nextTurn();
    [...ui.timers.values()][0]();
    await pending;
    assert.equal(ui.requests[0].options.signal.aborted, true);
    assert.deepEqual(ui.selected, []);
    assert.match(ui.status.textContent, /Không tra được tên khu vực/);
    assert.equal(ui.button.disabled, false);
});

test('provider labels stay within the existing 150-character limit for stored locations', async () => {
    const ui = fixture({ fetcher: async () => ({ ok: true, json: async () => ({ locality: 'Địa điểm '.repeat(30) }) }) });
    const pending = ui.click();
    ui.positions[0].resolve(currentPosition);
    await pending;
    assert.equal(Array.from(ui.selected[0]).length, 150);
});
