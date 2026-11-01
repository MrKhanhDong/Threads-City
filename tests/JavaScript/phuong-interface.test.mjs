import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { setupPhuongComposerBridge } from '../../resources/js/phuong-interface.js';

test('skip link is out of the page flow, visible on focus, and targets focusable main', () => {
    const css = readFileSync(new URL('../../resources/css/journal.css', import.meta.url), 'utf8');
    const layout = readFileSync(new URL('../../resources/views/layouts/threads.blade.php', import.meta.url), 'utf8');
    const hidden = css.match(/\.journal-body \.skip-link\s*\{([^}]+)\}/)?.[1];
    const focused = css.match(/\.journal-body \.skip-link:focus\s*\{([^}]+)\}/)?.[1];
    assert.match(hidden, /position:\s*fixed/);
    assert.match(hidden, /translateY\(calc\(-100% - 24px\)\)/);
    assert.match(hidden, /opacity:\s*0/);
    assert.match(hidden, /var\(--journal-surface\)/);
    assert.match(focused, /translateY\(0\)/);
    assert.match(focused, /opacity:\s*1/);
    assert.match(focused, /outline:\s*2px solid/);
    assert.doesNotMatch(hidden, /display:\s*none|visibility:\s*hidden/);
    assert.match(layout, /href="#main" class="skip-link"/);
    assert.match(layout, /<main id="main"[^>]+tabindex="-1"/);
});

function fixture(allowed, hasModal = true) {
    let click, shown = 0, prevented = false, checked = 0;
    const composer = {};
    const doc = {
        addEventListener(name, handler) { click = handler; },
        getElementById(id) { assert.equal(id, 'newThreadModal'); return hasModal ? composer : null; },
    };
    const win = { checkAuth() { checked++; return allowed; } };
    const Modal = { getOrCreateInstance(element) { assert.equal(element, composer); return { show() { shown++; } }; } };
    setupPhuongComposerBridge(Modal, doc, win);
    return {
        click(matches = true) { click({ target: { closest() { return matches ? {} : null; } }, preventDefault() { prevented = true; } }); },
        state() { return { shown, prevented, checked }; },
    };
}

test('Phuong new-post controls open the existing dev composer', () => {
    const ui = fixture(true);
    ui.click();
    assert.deepEqual(ui.state(), { shown: 1, prevented: true, checked: 1 });
});

test('guests keep dev login prompt and never open the signed-in composer', () => {
    const ui = fixture(false);
    ui.click();
    assert.deepEqual(ui.state(), { shown: 0, prevented: true, checked: 1 });
});

test('unrelated actions and missing composer remain harmless', () => {
    const unrelated = fixture(true);
    unrelated.click(false);
    assert.deepEqual(unrelated.state(), { shown: 0, prevented: false, checked: 0 });
    const noModal = fixture(true, false);
    noModal.click();
    assert.equal(noModal.state().shown, 0);
});
