import test from 'node:test';
import assert from 'node:assert/strict';
import { setupInsights } from '../../resources/js/insights.js';

function fixture() {
    const keys = ['views', 'likes', 'followers', 'interactions'];
    const links = keys.map(key => Object.assign(new EventTarget(), {
        dataset: { insightMetric: key }, href: `http://localhost/thong-tin-chi-tiet?days=30&metric=${key}#insight-summary`,
        attributes: key === 'views' ? { 'aria-current': 'true' } : {}, active: key === 'views',
        setAttribute(name, value) { this.attributes[name] = value; }, removeAttribute(name) { delete this.attributes[name]; }
    }));
    links.forEach(link => { link.classList = { toggle(name, value) { link.active = value; } }; });
    const panels = keys.map(key => ({ dataset: { insightPanel: key }, hidden: key !== 'views', getAttribute: () => `Biểu đồ ${key}` }));
    const input = { value: 'views' };
    const announcement = { textContent: '' };
    const pagination = [{ href: 'http://localhost/thong-tin-chi-tiet?days=30&metric=views&page=2' }];
    const print = new EventTarget();
    const root = {
        querySelector: selector => ({ '[data-insight-metric-input]': input, '[data-insight-announcement]': announcement, '[data-insights-print]': print })[selector],
        querySelectorAll: selector => ({ '[data-insight-metric]': links, '[data-insight-panel]': panels, '.journal-pagination a': pagination })[selector]
    };
    let savedUrl;
    let prints = 0;
    setupInsights({ querySelector: () => root }, { history: { replaceState(state, title, url) { savedUrl = url; } }, print() { prints++; } });
    const click = (key, modifiers = {}) => {
        const event = Object.assign(new Event('click', { cancelable: true }), { button: 0, ...modifiers });
        links.find(link => link.dataset.insightMetric === key).dispatchEvent(event);
        return event;
    };
    return { links, panels, input, announcement, pagination, print, click, savedUrl: () => savedUrl, prints: () => prints };
}

test('clicking each summary selects its actual chart and preserves selection for period and pagination', () => {
    const ui = fixture();
    for (const key of ['likes', 'followers', 'interactions', 'views']) {
        assert.equal(ui.click(key).defaultPrevented, true);
        assert.deepEqual(ui.panels.filter(panel => !panel.hidden).map(panel => panel.dataset.insightPanel), [key]);
        assert.deepEqual(ui.links.filter(link => link.active).map(link => link.dataset.insightMetric), [key]);
        assert.equal(ui.links.filter(link => link.attributes['aria-current'] === 'true').length, 1);
        assert.equal(ui.input.value, key);
        assert.match(ui.announcement.textContent, new RegExp(key));
        assert.equal(new URL(ui.pagination[0].href).searchParams.get('metric'), key);
        assert.equal(new URL(ui.pagination[0].href).searchParams.get('days'), '30');
        assert.equal(new URL(ui.pagination[0].href).searchParams.get('page'), '2');
        assert.equal(new URL(ui.savedUrl()).searchParams.get('metric'), key);
    }
});

test('modified clicks keep normal link behavior so charts can open in another tab', () => {
    const ui = fixture();
    assert.equal(ui.click('likes', { ctrlKey: true }).defaultPrevented, false);
    assert.equal(ui.click('followers', { metaKey: true }).defaultPrevented, false);
    assert.equal(ui.click('interactions', { button: 1 }).defaultPrevented, false);
    assert.equal(ui.input.value, 'views');
    assert.equal(ui.savedUrl(), undefined);
});

test('PDF action opens browser printing and other pages initialize without an insights panel', () => {
    const ui = fixture();
    ui.print.dispatchEvent(new Event('click'));
    assert.equal(ui.prints(), 1);
    assert.doesNotThrow(() => setupInsights({ querySelector: () => null }, {}));
});
