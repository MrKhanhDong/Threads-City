import test from 'node:test';
import assert from 'node:assert/strict';
import { setupDesktopColumns } from '../../resources/js/desktop-columns.js';

function fixture({ desktop = true, saved = null, blockedStorage = false } = {}) {
    const element = (dataset = {}) => Object.assign(new EventTarget(), {
        dataset, hidden: false, textContent: '', attributes: {}, scrollTop: 0, classes: new Set(),
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        focus() { this.focused = true; }, contains: () => false
    });
    const withClasses = item => Object.assign(item, { classList: {
        add(...names) { names.forEach(name => item.classes.add(name)); },
        remove(...names) { names.forEach(name => item.classes.delete(name)); },
        toggle(name, value) { if (value) item.classes.add(name); else item.classes.delete(name); },
        contains: name => item.classes.has(name)
    } });
    const primary = withClasses(element({ columnSlot: 'primary' }));
    const secondary = withClasses(element({ columnSlot: 'secondary' }));
    secondary.hidden = true;
    const frame = element();
    let loads = 0;
    let source = 'about:blank';
    Object.defineProperty(frame, 'src', { get: () => source, set: value => { source = value; loads++; } });
    frame.contentWindow = { location: { href: 'http://localhost/search' } };
    frame.contentDocument = { title: 'Tìm kiếm — Threads', querySelector: () => null };
    const choices = ['search', 'activity'].map(key => Object.assign(element({ columnChoice: key, columnUrl: `http://localhost/${key}` }), { textContent: key }));
    const handles = ['primary', 'secondary'].map(key => element({ columnDrag: key }));
    const select = element();
    const title = element();
    const status = element();
    const close = element();
    const add = element();
    const announcement = element();
    const feedGroup = element();
    let pickerHides = 0;
    let pickerUpdates = 0;
    const workspace = withClasses(element());
    const single = { '[data-column-slot="secondary"]': secondary, '[data-column-slot="primary"]': primary,
        '[data-desktop-column-frame]': frame, '[data-column-select]': select, '[data-column-title]': title,
        '[data-column-status]': status, '[data-column-announcement]': announcement, '.desktop-column-add': add, '[data-column-close]': close, '[data-column-feed-group]': feedGroup };
    workspace.querySelector = selector => single[selector];
    workspace.querySelectorAll = selector => ({ '[data-column-choice]': choices, '[data-column-drag]': handles, '[data-column-slot]': [primary, secondary] })[selector];
    const body = withClasses(element());
    const media = Object.assign(new EventTarget(), { matches: desktop });
    const storage = new Map(saved === null ? [] : [['threads-desktop-columns', saved]]);
    const pageWindow = { location: new URL('http://localhost/'), scrollY: 125, matchMedia: () => media,
        sessionStorage: { getItem: key => storage.get(key), setItem(key, value) { if (blockedStorage) throw new Error('blocked'); storage.set(key, value); }, removeItem: key => storage.delete(key) },
        requestAnimationFrame: callback => callback(), scrollTo: options => { pageWindow.restoredScroll = options.top; } };
    setupDesktopColumns({ querySelector: () => workspace, body }, pageWindow, { getInstance: () => ({ hide() { pickerHides++; }, update() { pickerUpdates++; } }) });
    const drag = (target, type) => {
        const event = Object.assign(new Event(type, { cancelable: true }), { dataTransfer: { setData() {} } });
        target.dispatchEvent(event);
        return event;
    };
    return { primary, secondary, frame, choices, handles, select, status, close, add, workspace, body, media, storage, pageWindow, feedGroup, pickerHides: () => pickerHides, pickerUpdates: () => pickerUpdates, loads: () => loads, drag };
}

test('feed submenu expansion repositions the picker without dismissing it; choosing a tab dismisses it', () => {
    const ui = fixture();
    ui.feedGroup.dispatchEvent(new Event('toggle'));
    assert.equal(ui.pickerUpdates(), 1);
    assert.equal(ui.pickerHides(), 0);
    assert.equal(ui.loads(), 0);
    ui.choices[0].dispatchEvent(new Event('click'));
    assert.equal(ui.pickerHides(), 1);
    assert.equal(ui.select.focused, true);
});

test('desktop opens a real tab, preserves primary reading position and clears loading after navigation', () => {
    const ui = fixture();
    ui.choices[0].dispatchEvent(new Event('click'));
    assert.equal(ui.secondary.hidden, false);
    assert.equal(ui.frame.src, 'http://localhost/search');
    assert.equal(ui.primary.scrollTop, 125);
    assert.equal(ui.workspace.classList.contains('is-split'), true);
    assert.equal(ui.secondary.attributes['aria-busy'], 'true');
    ui.frame.dispatchEvent(new Event('load'));
    assert.equal(ui.status.hidden, true);
    assert.equal(ui.secondary.attributes['aria-busy'], undefined);
    assert.equal(ui.frame.title, 'Cột Tìm kiếm');
});

test('dragging either column swaps its position without reloading its content and cancelled drags do nothing', () => {
    const ui = fixture();
    ui.choices[0].dispatchEvent(new Event('click'));
    const loads = ui.loads();
    ui.drag(ui.handles[1], 'dragstart');
    assert.equal(ui.drag(ui.primary, 'dragover').defaultPrevented, true);
    ui.drag(ui.primary, 'drop');
    assert.equal(ui.workspace.classList.contains('is-reversed'), true);
    assert.equal(ui.workspace.classList.contains('is-reordering'), false);
    assert.equal(ui.loads(), loads);
    ui.drag(ui.handles[0], 'dragstart');
    ui.drag(ui.handles[0], 'dragend');
    assert.equal(ui.workspace.classList.contains('is-reversed'), true);
    ui.handles[0].dispatchEvent(new Event('click'));
    assert.equal(ui.workspace.classList.contains('is-reversed'), false);
    assert.equal(ui.loads(), loads);
});

test('remove column releases the frame, clears saved selection and returns focus and scroll', () => {
    const ui = fixture();
    ui.choices[0].dispatchEvent(new Event('click'));
    ui.handles[1].dispatchEvent(new Event('click'));
    ui.primary.scrollTop = 500;
    ui.close.dispatchEvent(new Event('click'));
    assert.equal(ui.secondary.hidden, true);
    assert.equal(ui.frame.src, 'about:blank');
    assert.equal(ui.workspace.classList.contains('is-split'), false);
    assert.equal(ui.workspace.classList.contains('is-reversed'), false);
    assert.equal(ui.body.classList.contains('journal-split-active'), false);
    assert.equal(ui.storage.size, 0);
    assert.equal(ui.add.focused, true);
    assert.equal(ui.pageWindow.restoredScroll, 500);
});

test('selected tab and column order survive navigation; corrupt or blocked storage is harmless', () => {
    const ui = fixture({ saved: JSON.stringify({ selected: 'activity', reversed: true }) });
    assert.equal(ui.frame.src, 'http://localhost/activity');
    assert.equal(ui.workspace.classList.contains('is-reversed'), true);
    assert.equal(fixture({ saved: '{broken' }).secondary.hidden, true);
    assert.equal(fixture({ saved: JSON.stringify({ selected: 'unknown', reversed: true }) }).secondary.hidden, true);
    const blocked = fixture({ blockedStorage: true });
    assert.doesNotThrow(() => blocked.choices[0].dispatchEvent(new Event('click')));
});

test('tablet and mobile never open columns; resizing away from desktop closes an existing column', () => {
    const small = fixture({ desktop: false, saved: JSON.stringify({ selected: 'search' }) });
    small.choices[0].dispatchEvent(new Event('click'));
    assert.equal(small.secondary.hidden, true);
    assert.equal(small.loads(), 0);
    const ui = fixture();
    ui.choices[0].dispatchEvent(new Event('click'));
    ui.media.dispatchEvent(Object.assign(new Event('change'), { matches: false }));
    assert.equal(ui.secondary.hidden, true);
    assert.equal(ui.body.classList.contains('journal-split-active'), false);
});

test('embedded tabs cannot recursively initialize more columns and Google login opens at top level', () => {
    const pageDocument = new EventTarget();
    pageDocument.querySelector = () => { throw new Error('Nested columns initialized'); };
    setupDesktopColumns(pageDocument, { frameElement: { hasAttribute: () => true }, location: new URL('http://localhost/dang-nhap') });
    const link = { href: 'http://localhost/auth/google' };
    const event = new Event('click');
    Object.defineProperty(event, 'target', { value: { closest: () => link } });
    pageDocument.dispatchEvent(event);
    assert.equal(link.target, '_top');
});
