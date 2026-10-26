import test from 'node:test';
import assert from 'node:assert/strict';
import { setupSidebarDrawer } from '../../resources/js/sidebar-drawer.js';

function fixture() {
    let isOpen = false;
    let hides = 0;
    const drawer = Object.assign(new EventTarget(), { classList: { contains: () => isOpen } });
    const trigger = { attributes: {}, setAttribute(name, value) { this.attributes[name] = value; } };
    const desktop = new EventTarget();
    const instance = { hide() { hides++; drawer.dispatchEvent(new Event('hide.bs.offcanvas')); } };
    setupSidebarDrawer({ getOrCreateInstance: () => instance, getInstance: () => instance }, {
        querySelector: selector => selector === '[data-sidebar-drawer]' ? drawer : trigger
    }, { matchMedia: () => desktop });
    return { drawer, trigger, desktop, hides: () => hides, open() { isOpen = true; drawer.dispatchEvent(new Event('show.bs.offcanvas')); } };
}

test('drawer opening and dismissal update the header button state', () => {
    const ui = fixture();
    ui.open();
    assert.equal(ui.trigger.attributes['aria-expanded'], 'true');
    ui.drawer.dispatchEvent(new Event('hide.bs.offcanvas'));
    assert.equal(ui.trigger.attributes['aria-expanded'], 'false');
});

test('compose waits for drawer dismissal before opening the editor', () => {
    const ui = fixture();
    let clicks = 0;
    const compose = { click() { clicks++; } };
    ui.open();
    const click = new Event('click', { cancelable: true });
    Object.defineProperty(click, 'target', { value: { closest: () => compose } });
    ui.drawer.dispatchEvent(click);
    assert.equal(click.defaultPrevented, true);
    assert.equal(ui.hides(), 1);
    assert.equal(clicks, 0);
    ui.drawer.dispatchEvent(new Event('hidden.bs.offcanvas'));
    assert.equal(clicks, 1);
    ui.drawer.dispatchEvent(new Event('hidden.bs.offcanvas'));
    assert.equal(clicks, 1);
});

test('resizing to desktop dismisses the drawer and pages without one initialize safely', () => {
    const ui = fixture();
    ui.desktop.dispatchEvent(Object.assign(new Event('change'), { matches: false }));
    assert.equal(ui.hides(), 0);
    ui.desktop.dispatchEvent(Object.assign(new Event('change'), { matches: true }));
    assert.equal(ui.hides(), 1);
    assert.doesNotThrow(() => setupSidebarDrawer({}, { querySelector: () => null }, {}));
});
