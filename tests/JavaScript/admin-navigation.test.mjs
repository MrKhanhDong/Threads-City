import test from 'node:test';
import assert from 'node:assert/strict';
import { setupAdminNavigation } from '../../resources/js/admin-navigation.js';

function fixture({ desktop = false, filters = false } = {}) {
    const navigation = new EventTarget();
    const toggles = [true, false].map(label => ({
        attributes: label ? { 'aria-label': 'Mở menu quản trị' } : {},
        setAttribute(name, value) { this.attributes[name] = value; },
        hasAttribute(name) { return Object.hasOwn(this.attributes, name); },
    }));
    const desktopMedia = Object.assign(new EventTarget(), { matches: desktop });
    const filterMedia = Object.assign(new EventTarget(), { matches: !desktop });
    const disclosure = { open: true, dataset: { hasFilters: String(filters) } };
    const pageDocument = Object.assign(new EventTarget(), {
        querySelector: () => navigation,
        querySelectorAll: selector => selector === '[data-admin-menu-toggle]' ? toggles : [disclosure],
    });
    let hides = 0;
    setupAdminNavigation({ getInstance: () => ({ hide() {
        hides++;
        navigation.dispatchEvent(new Event('hide.bs.offcanvas'));
    } }) }, pageDocument, {
        matchMedia: query => query === '(min-width: 992px)' ? desktopMedia : filterMedia,
    });
    return { navigation, toggles, desktopMedia, filterMedia, disclosure, pageDocument, hides: () => hides };
}

function dispatchTarget(target, type, closest) {
    const event = new Event(type);
    Object.defineProperty(event, 'target', { value: { closest } });
    target.dispatchEvent(event);
}

test('all menu triggers expose the drawer state on opening and dismissal', () => {
    const ui = fixture();
    ui.navigation.dispatchEvent(new Event('show.bs.offcanvas'));
    ui.toggles.forEach(toggle => assert.equal(toggle.attributes['aria-expanded'], 'true'));
    assert.equal(ui.toggles[0].attributes['aria-label'], 'Đóng menu quản trị');
    ui.navigation.dispatchEvent(new Event('hide.bs.offcanvas'));
    ui.toggles.forEach(toggle => assert.equal(toggle.attributes['aria-expanded'], 'false'));
    assert.equal(ui.toggles[0].attributes['aria-label'], 'Mở menu quản trị');
});

test('mobile navigation closes on a link and resets when switching to desktop', () => {
    const ui = fixture();
    dispatchTarget(ui.navigation, 'click', () => null);
    assert.equal(ui.hides(), 0);
    dispatchTarget(ui.navigation, 'click', () => ({}));
    assert.equal(ui.hides(), 1);
    ui.desktopMedia.matches = true;
    ui.desktopMedia.dispatchEvent(Object.assign(new Event('change'), { matches: true }));
    assert.equal(ui.hides(), 2);
    dispatchTarget(ui.navigation, 'click', () => ({}));
    assert.equal(ui.hides(), 2);
});

test('mobile filters collapse only when no filters are applied and expand on desktop', () => {
    const ui = fixture();
    assert.equal(ui.disclosure.open, false);
    assert.equal(fixture({ filters: true }).disclosure.open, true);
    assert.equal(fixture({ desktop: true }).disclosure.open, true);
    ui.filterMedia.dispatchEvent(Object.assign(new Event('change'), { matches: false }));
    assert.equal(ui.disclosure.open, true);
});

test('validation reveals fields inside a collapsed disclosure', () => {
    const ui = fixture();
    const action = { open: false };
    dispatchTarget(ui.pageDocument, 'invalid', () => action);
    assert.equal(action.open, true);
    assert.doesNotThrow(() => dispatchTarget(ui.pageDocument, 'invalid', () => null));
});

test('public pages initialize without a drawer or offcanvas instance', () => {
    assert.doesNotThrow(() => setupAdminNavigation({}, { querySelector: () => null }, {}));
});
