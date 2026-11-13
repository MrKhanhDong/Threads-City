import test from 'node:test';
import assert from 'node:assert/strict';
import { setupDesktopColumns } from '../../resources/js/desktop-columns.js';

function fixture({ desktop = true, saved = null, blockedStorage = false, hasLimitMessage = true } = {}) {
    const element = (dataset = {}) => {
        const item = Object.assign(new EventTarget(), {
            dataset, hidden: false, textContent: '', attributes: {}, scrollTop: 0, style: {}, classes: new Set(),
            setAttribute(name, value) { this.attributes[name] = value; }, removeAttribute(name) { delete this.attributes[name]; },
            focus() { this.focused = true; }, contains: () => false, closest: () => null,
            setPointerCapture(id) { this.pointer = id; }, hasPointerCapture(id) { return this.pointer === id; }, releasePointerCapture() { this.pointer = null; },
        });
        item.classList = {
            add(...names) { names.forEach(name => item.classes.add(name)); },
            remove(...names) { names.forEach(name => item.classes.delete(name)); },
            toggle(name, value) { if (value) item.classes.add(name); else item.classes.delete(name); },
            contains: name => item.classes.has(name),
        };
        return item;
    };
    const primary = element({ columnSlot: 'primary' });
    const primaryHeader = element({ columnDrag: 'primary' });
    const panels = ['secondary', 'tertiary'].map(id => {
        const panel = element({ columnSlot: id });
        panel.hidden = true;
        const frame = element();
        let loads = 0;
        let source = 'about:blank';
        Object.defineProperty(frame, 'src', { get: () => source, set: value => { source = value; loads++; } });
        frame.contentWindow = { location: { href: 'http://localhost/search' } };
        frame.contentDocument = { title: 'Tìm kiếm — Threads', querySelector: () => null };
        const select = element(), title = element(), status = element(), close = element(), header = element({ columnDrag: id });
        const single = { '[data-desktop-column-frame]': frame, '[data-column-select]': select, '[data-column-title]': title, '[data-column-status]': status, '[data-column-close]': close, '[data-column-drag]': header };
        panel.querySelector = selector => single[selector];
        return { element: panel, frame, select, title, status, close, header, loads: () => loads };
    });
    const choices = ['search', 'activity', 'home'].map(key => Object.assign(element({ columnChoice: key, columnUrl: `http://localhost/${key}` }), { textContent: key }));
    const handles = [primaryHeader, ...panels.map(panel => panel.header)];
    const add = element(), announcement = element(), feedGroup = element(), count = element(), limit = element();
    let pickerHides = 0, pickerUpdates = 0;
    const workspace = element();
    const slots = [primary, ...panels.map(panel => panel.element)];
    workspace.getBoundingClientRect = () => ({ left: 0, top: 0, width: 1200, height: 800 });
    slots.forEach(slot => {
        Object.defineProperties(slot, { offsetLeft: { get: () => Number(slot.style.order || 0) * 400 }, offsetTop: { value: 0 }, offsetWidth: { value: 400 }, offsetHeight: { value: 800 } });
        slot.getBoundingClientRect = () => ({ left: slot.offsetLeft + (parseFloat((slot.style.transform || '').split('(')[1]) || 0), top: 0, width: 400, height: 800 });
    });
    workspace.querySelector = selector => ({ '[data-column-slot="primary"]': primary, '.desktop-column-add': add,
        '[data-column-announcement]': announcement, '[data-column-feed-group]': feedGroup,
        '[data-column-count-label]': count, '[data-column-limit]': hasLimitMessage ? limit : null,
        ...Object.fromEntries(panels.map(panel => [`[data-column-slot="${panel.element.dataset.columnSlot}"]`, panel.element])) })[selector];
    workspace.querySelectorAll = selector => ({ '.desktop-secondary-column': panels.map(panel => panel.element), '[data-column-choice]': choices, '[data-column-drag]': handles, '[data-column-slot]': slots })[selector];
    const body = element();
    const media = Object.assign(new EventTarget(), { matches: desktop });
    const storage = new Map(saved === null ? [] : [['threads-desktop-columns', saved]]);
    const pageWindow = { location: new URL('http://localhost/'), scrollY: 125, matchMedia: query => query.includes('reduced-motion') ? { matches: false } : media,
        sessionStorage: { getItem: key => storage.get(key), setItem(key, value) { if (blockedStorage) throw new Error('blocked'); storage.set(key, value); }, removeItem: key => storage.delete(key) },
        requestAnimationFrame: callback => { callback(); return null; }, cancelAnimationFrame() {}, scrollTo: options => { pageWindow.restoredScroll = options.top; } };
    setupDesktopColumns({ querySelector: () => workspace, body }, pageWindow, { getInstance: () => ({ hide() { pickerHides++; }, update() { pickerUpdates++; } }) });
    const pointer = (target, type, x = 200, options = {}) => {
        const event = Object.assign(new Event(type, { cancelable: true }), { clientX: x, clientY: 30, button: 0, pointerId: 1 });
        if (options.interactive) Object.defineProperty(event, 'target', { value: { closest: () => ({}) } });
        target.dispatchEvent(event);
        return event;
    };
    const visualOrder = () => slots.filter(slot => slot === primary || !slot.hidden).sort((a, b) => Number(a.style.order) - Number(b.style.order)).map(slot => slot.dataset.columnSlot);
    return { primary, panels, choices, handles, add, workspace, body, media, storage, pageWindow, feedGroup, count, limit, pointer, visualOrder, pickerHides: () => pickerHides, pickerUpdates: () => pickerUpdates };
}

test('column setup completes when the layout omits the optional limit message', () => {
    const ui = fixture({ hasLimitMessage: false });
    assert.equal(ui.workspace.dataset.columnCount, '1');
    assert.equal(ui.add.attributes['aria-label'], 'Thêm cột');
    ui.choices[0].dispatchEvent(new Event('click'));
    assert.equal(ui.workspace.dataset.columnCount, '2');
    assert.equal(ui.panels[0].frame.src, 'http://localhost/search');
});

test('submenu remains open until selection and selecting a tab opens its own column', () => {
    const ui = fixture();
    ui.feedGroup.dispatchEvent(new Event('toggle'));
    assert.equal(ui.pickerUpdates(), 1);
    assert.equal(ui.pickerHides(), 0);
    ui.choices[0].dispatchEvent(new Event('click'));
    assert.equal(ui.pickerHides(), 1);
    assert.equal(ui.panels[0].header.focused, true);
    assert.equal(ui.panels[0].frame.src, 'http://localhost/search');
    assert.equal(ui.primary.scrollTop, 125);
    ui.panels[0].frame.dispatchEvent(new Event('load'));
    assert.equal(ui.panels[0].status.hidden, true);
    assert.equal(ui.panels[0].frame.title, 'Cột Tìm kiếm');
});

test('three is a hard total limit; changing an existing column never replaces another one', () => {
    const ui = fixture();
    ui.choices[0].dispatchEvent(new Event('click'));
    ui.choices[1].dispatchEvent(new Event('click'));
    assert.equal(ui.workspace.dataset.columnCount, '3');
    assert.equal(ui.count.textContent, '3/3');
    assert.equal(ui.limit.hidden, false);
    assert.ok(ui.choices.every(choice => choice.disabled));
    const before = ui.panels.map(panel => panel.loads());
    ui.choices[2].dispatchEvent(new Event('click'));
    assert.deepEqual(ui.panels.map(panel => panel.loads()), before);
    ui.panels[0].select.value = 'home';
    ui.panels[0].select.dispatchEvent(new Event('change'));
    assert.equal(ui.panels[0].frame.src, 'http://localhost/home');
    assert.equal(ui.panels[1].frame.src, 'http://localhost/activity');
    assert.equal(ui.panels[1].loads(), before[1]);
});

test('any pair of columns can swap without reloading frames; keyboard movement also preserves content', () => {
    const ui = fixture();
    ui.choices[0].dispatchEvent(new Event('click'));
    ui.choices[1].dispatchEvent(new Event('click'));
    const loads = ui.panels.map(panel => panel.loads());
    ui.primary.scrollTop = 350;
    ui.pointer(ui.handles[2], 'pointerdown', 1000);
    assert.equal(ui.pointer(ui.handles[2], 'pointermove', 180).defaultPrevented, true);
    ui.pointer(ui.handles[2], 'pointerup', 180);
    assert.deepEqual(ui.visualOrder(), ['tertiary', 'primary', 'secondary']);
    assert.equal(ui.workspace.classList.contains('is-reordering'), false);
    ui.handles[2].dispatchEvent(Object.assign(new Event('keydown', { cancelable: true }), { key: 'ArrowRight' }));
    assert.deepEqual(ui.visualOrder(), ['primary', 'tertiary', 'secondary']);
    ui.pointer(ui.handles[1], 'pointerdown', 1000);
    ui.pointer(ui.handles[1], 'pointermove', 100);
    ui.handles[1].dispatchEvent(Object.assign(new Event('keydown', { cancelable: true }), { key: 'Escape' }));
    assert.deepEqual(ui.visualOrder(), ['primary', 'tertiary', 'secondary']);
    assert.deepEqual(ui.panels.map(panel => panel.loads()), loads);
    assert.equal(ui.primary.scrollTop, 350);
});

test('clicks and tiny pointer movements do not reorder; menus and form controls remain usable', () => {
    const ui = fixture();
    ui.choices[0].dispatchEvent(new Event('click'));
    ui.pointer(ui.handles[0], 'pointerdown', 200);
    ui.pointer(ui.handles[0], 'pointermove', 203);
    ui.pointer(ui.handles[0], 'pointerup', 203);
    assert.deepEqual(ui.visualOrder(), ['primary', 'secondary']);
    ui.pointer(ui.handles[0], 'pointerdown', 200, { interactive: true });
    ui.pointer(ui.handles[0], 'pointermove', 700);
    ui.pointer(ui.handles[0], 'pointerup', 700);
    assert.deepEqual(ui.visualOrder(), ['primary', 'secondary']);
    assert.equal(ui.workspace.classList.contains('is-reordering'), false);
});

test('removing one column preserves the other; removing the last restores single-column reading', () => {
    const ui = fixture();
    ui.choices[0].dispatchEvent(new Event('click'));
    ui.choices[1].dispatchEvent(new Event('click'));
    const remainingLoads = ui.panels[1].loads();
    ui.panels[0].close.dispatchEvent(new Event('click'));
    assert.equal(ui.workspace.dataset.columnCount, '2');
    assert.equal(ui.panels[0].frame.src, 'about:blank');
    assert.equal(ui.panels[1].loads(), remainingLoads);
    assert.equal(ui.limit.hidden, true);
    assert.ok(ui.choices.every(choice => !choice.disabled));
    ui.choices[2].dispatchEvent(new Event('click'));
    assert.equal(ui.workspace.dataset.columnCount, '3');
    ui.panels[0].close.dispatchEvent(new Event('click'));
    ui.primary.scrollTop = 500;
    ui.panels[1].close.dispatchEvent(new Event('click'));
    assert.equal(ui.workspace.dataset.columnCount, '1');
    assert.equal(ui.body.classList.contains('journal-split-active'), false);
    assert.equal(ui.storage.size, 0);
    assert.equal(ui.add.focused, true);
    assert.equal(ui.pageWindow.restoredScroll, 500);
});

test('three-column order survives navigation and previous two-column preferences migrate safely', () => {
    const ui = fixture({ saved: JSON.stringify({ columns: [{ id: 'secondary', tab: 'search' }, { id: 'tertiary', tab: 'activity' }], order: ['tertiary', 'primary', 'secondary'] }) });
    assert.deepEqual(ui.visualOrder(), ['tertiary', 'primary', 'secondary']);
    assert.equal(ui.panels[0].frame.src, 'http://localhost/search');
    const old = fixture({ saved: JSON.stringify({ selected: 'activity', reversed: true }) });
    assert.deepEqual(old.visualOrder(), ['secondary', 'primary']);
    assert.ok(Array.isArray(JSON.parse(old.storage.get('threads-desktop-columns')).columns));
    assert.equal(fixture({ saved: '{broken' }).workspace.dataset.columnCount, '1');
    const invalid = fixture({ saved: JSON.stringify({ columns: [{ id: 'secondary', tab: 'unknown' }, { id: 'secondary', tab: 'search' }, { id: 'tertiary', tab: 'activity' }], order: ['unknown', 'primary', 'primary'] }) });
    assert.ok(Number(invalid.workspace.dataset.columnCount) <= 3);
    const blocked = fixture({ blockedStorage: true });
    assert.doesNotThrow(() => blocked.choices[0].dispatchEvent(new Event('click')));
});

test('tablet and mobile cannot open columns; shrinking the viewport releases both extra frames', () => {
    const small = fixture({ desktop: false, saved: JSON.stringify({ selected: 'search' }) });
    small.choices[0].dispatchEvent(new Event('click'));
    assert.equal(small.workspace.dataset.columnCount, '1');
    assert.ok(small.panels.every(panel => panel.loads() === 0));
    const ui = fixture();
    ui.choices[0].dispatchEvent(new Event('click'));
    ui.choices[1].dispatchEvent(new Event('click'));
    ui.media.dispatchEvent(Object.assign(new Event('change'), { matches: false }));
    assert.equal(ui.workspace.dataset.columnCount, '1');
    assert.ok(ui.panels.every(panel => panel.element.hidden && panel.frame.src === 'about:blank'));
});

test('embedded tabs cannot recursively initialize columns and Google login opens at top level', () => {
    const pageDocument = new EventTarget();
    pageDocument.querySelector = () => { throw new Error('Nested columns initialized'); };
    setupDesktopColumns(pageDocument, { frameElement: { hasAttribute: () => true }, location: new URL('http://localhost/dang-nhap') });
    const link = { href: 'http://localhost/auth/google' };
    const event = new Event('click');
    Object.defineProperty(event, 'target', { value: { closest: () => link } });
    pageDocument.dispatchEvent(event);
    assert.equal(link.target, '_top');
});
