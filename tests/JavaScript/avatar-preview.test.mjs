import test from 'node:test';
import assert from 'node:assert/strict';
import { setupAvatarPreview } from '../../resources/js/avatar-preview.js';

function fixture() {
    const savedAvatar = { name: 'saved-avatar' }, revoked = [], created = [];
    const preview = { childNodes: [savedAvatar], replaceChildren(...nodes) { this.childNodes = nodes; } };
    const classes = new Map();
    const status = { textContent: '', classList: { toggle(name, enabled) { classes.set(name, enabled); } } };
    const form = {
        querySelector: selector => selector === '[data-avatar-preview]' ? preview : status,
        addEventListener(name, callback) { this[name] = callback; },
    };
    const input = {
        value: '', files: [], closest: () => form,
        addEventListener(name, callback) { this[name] = callback; },
    };
    const doc = { querySelector: () => input, createElement: () => ({}) };
    const win = { URL: {
        createObjectURL(file) { created.push(file); return `blob:avatar-${created.length}`; },
        revokeObjectURL: url => revoked.push(url),
    } };
    setupAvatarPreview(doc, win);
    return {
        savedAvatar, preview, status, classes, input, form, created, revoked,
        choose(file) { input.files = file ? [file] : []; input.value = file?.name || ''; input.change(); },
    };
}

const photo = { name: 'avatar.png', type: 'image/png', size: 1024 };

test('choosing an avatar immediately previews it and explains that saving is still required', () => {
    const ui = fixture();
    ui.choose(photo);
    assert.equal(ui.preview.childNodes[0].src, 'blob:avatar-1');
    assert.equal(ui.preview.childNodes[0].alt, 'Ảnh đại diện xem trước');
    assert.match(ui.status.textContent, /avatar\.png/);
    assert.match(ui.status.textContent, /Lưu thay đổi/);
    assert.equal(ui.classes.get('text-danger'), false);
    assert.equal(ui.input.value, 'avatar.png');
});

test('invalid or oversized selections keep the saved avatar and clear the rejected file', () => {
    for (const invalid of [{ ...photo, type: 'image/svg+xml' }, { ...photo, size: 2 * 1024 * 1024 + 1 }]) {
        const ui = fixture();
        ui.choose(invalid);
        assert.equal(ui.preview.childNodes[0], ui.savedAvatar);
        assert.equal(ui.created.length, 0);
        assert.equal(ui.input.value, '');
        assert.equal(ui.classes.get('text-danger'), true);
        assert.match(ui.status.textContent, /2 MB/);
    }
});

test('replacing and resetting a selection releases object URLs and restores the original avatar', () => {
    const ui = fixture();
    ui.choose(photo);
    ui.choose({ ...photo, name: 'new-avatar.png' });
    assert.deepEqual(ui.revoked, ['blob:avatar-1']);
    assert.equal(ui.preview.childNodes[0].src, 'blob:avatar-2');
    ui.form.reset();
    assert.deepEqual(ui.revoked, ['blob:avatar-1', 'blob:avatar-2']);
    assert.equal(ui.preview.childNodes[0], ui.savedAvatar);
    assert.equal(ui.status.textContent, '');
});

test('unreadable images show an error and stale image errors cannot remove a newer selection', () => {
    const ui = fixture();
    ui.choose(photo);
    const previousImage = ui.preview.childNodes[0];
    ui.choose(photo);
    previousImage.onerror();
    assert.equal(ui.preview.childNodes[0].src, 'blob:avatar-2');
    ui.preview.childNodes[0].onerror();
    assert.equal(ui.preview.childNodes[0], ui.savedAvatar);
    assert.equal(ui.input.value, '');
    assert.match(ui.status.textContent, /Không đọc được ảnh/);
});

test('clearing a selection restores the saved avatar and pages without the form initialize safely', () => {
    const ui = fixture();
    ui.choose(photo);
    ui.choose(null);
    assert.equal(ui.preview.childNodes[0], ui.savedAvatar);
    assert.deepEqual(ui.revoked, ['blob:avatar-1']);
    assert.doesNotThrow(() => setupAvatarPreview({ querySelector: () => null }, {}));
});
