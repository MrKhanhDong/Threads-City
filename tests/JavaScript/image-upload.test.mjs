import test from 'node:test';
import assert from 'node:assert/strict';
import { prepareThreadImage, uploadThreadImage } from '../../resources/js/image-upload.js';

const nextTurn = () => new Promise(resolve => setImmediate(resolve));
const imageFile = (type = 'image/jpeg', size = 400 * 1024) => new File([new Uint8Array(size)], 'holiday.photo.' + type.split('/')[1], { type, lastModified: 123 });

function canvasFixture({ width = 4000, height = 3000, blob = new Blob([new Uint8Array(80 * 1024)], { type: 'image/webp' }), fails = false } = {}) {
    const revoked = [], drawings = [];
    const canvas = {
        getContext: () => ({ drawImage: (...args) => drawings.push(args) }),
        toBlob(callback, type, quality) { assert.equal(type, 'image/webp'); assert.equal(quality, 0.82); callback(blob); },
    };
    const env = {
        File,
        URL: { createObjectURL: () => 'blob:preview', revokeObjectURL: url => revoked.push(url) },
        Image: class {
            naturalWidth = width;
            naturalHeight = height;
            set src(value) { queueMicrotask(() => fails ? this.onerror(new Error('decode failed')) : this.onload()); }
        },
        document: { createElement(tag) { assert.equal(tag, 'canvas'); return canvas; } },
    };
    return { env, canvas, revoked, drawings };
}

test('large JPEG and PNG images shrink to 1920 pixels and send a smaller WebP with the correct filename', async () => {
    for (const type of ['image/jpeg', 'image/png']) {
        const ui = canvasFixture();
        const original = imageFile(type);
        const result = await prepareThreadImage(original, ui.env);
        assert.equal(result.type, 'image/webp');
        assert.equal(result.name, 'holiday.photo.webp');
        assert.equal(result.lastModified, original.lastModified);
        assert.ok(result.size < original.size);
        assert.equal(ui.canvas.width, 1920);
        assert.equal(ui.canvas.height, 1440);
        assert.deepEqual(ui.drawings[0].slice(1), [0, 0, 1920, 1440]);
        assert.deepEqual(ui.revoked, ['blob:preview']);
    }
});

test('small, GIF and WebP files remain unchanged without decoding or losing animation', async () => {
    for (const file of [imageFile('image/jpeg', 200), imageFile('image/gif'), imageFile('image/webp')]) {
        assert.equal(await prepareThreadImage(file, {}), file);
    }
});

test('compression does not enlarge pixel dimensions', async () => {
    const ui = canvasFixture({ width: 800, height: 600 });
    await prepareThreadImage(imageFile(), ui.env);
    assert.equal(ui.canvas.width, 800);
    assert.equal(ui.canvas.height, 600);
});

test('decode errors, unsupported encoders, larger output and missing output fall back to the original file', async () => {
    for (const options of [
        { fails: true }, { blob: null },
        { blob: new Blob([new Uint8Array(600 * 1024)], { type: 'image/webp' }) },
        { blob: new Blob(['png'], { type: 'image/png' }) },
    ]) {
        const ui = canvasFixture(options);
        const file = imageFile();
        assert.equal(await prepareThreadImage(file, ui.env), file);
        assert.deepEqual(ui.revoked, ['blob:preview']);
    }
});

function uploadFixture() {
    const requests = [], stages = [], progress = [];
    const env = {
        ...canvasFixture().env,
        FormData,
        XMLHttpRequest: class {
            upload = {};
            headers = {};
            constructor() { requests.push(this); }
            open(method, url) { this.method = method; this.url = url; }
            setRequestHeader(name, value) { this.headers[name] = value; }
            send(body) { this.body = body; }
            abort() { this.onabort(); }
        },
    };
    const controller = new AbortController();
    const options = { env, signal: controller.signal, onStage: stage => stages.push(stage), onProgress: value => progress.push(value) };
    const upload = (file = imageFile()) => uploadThreadImage(file, '/threads/upload-media', 'csrf-token', options);
    return { requests, stages, progress, options, controller, upload };
}

test('upload sends the optimized file with CSRF, reports transfer progress separately from saving and returns server media', async () => {
    const ui = uploadFixture();
    const pending = ui.upload();
    await nextTurn();
    const xhr = ui.requests[0];
    assert.equal(xhr.method, 'POST');
    assert.equal(xhr.url, '/threads/upload-media');
    assert.equal(xhr.timeout, 45000);
    assert.equal(xhr.responseType, 'json');
    assert.equal(xhr.headers['X-CSRF-TOKEN'], 'csrf-token');
    assert.equal(xhr.headers.Accept, 'application/json');
    assert.equal(xhr.body.get('image').type, 'image/webp');
    assert.equal(xhr.body.get('image').size, 80 * 1024);
    xhr.upload.onprogress({ lengthComputable: true, loaded: 40, total: 100 });
    xhr.upload.onprogress({ lengthComputable: true, loaded: 100, total: 100 });
    xhr.upload.onprogress({ lengthComputable: true, loaded: 0, total: 0 });
    xhr.upload.onload();
    assert.deepEqual(ui.progress, [40, 100]);
    assert.deepEqual(ui.stages, ['preparing', 'uploading', 'saving']);
    xhr.status = 200;
    xhr.response = { id: 55, url: '/media/55' };
    xhr.onload();
    assert.deepEqual(await pending, { id: 55, url: '/media/55' });
});

test('unsupported or oversized files are rejected before starting a network request', async () => {
    const ui = uploadFixture();
    await assert.rejects(ui.upload(new File(['text'], 'notes.txt', { type: 'text/plain' })), /Chọn ảnh/);
    await assert.rejects(ui.upload(imageFile('image/jpeg', 6 * 1024 * 1024)), /5 MB/);
    assert.equal(ui.requests.length, 0);
});

test('HTTP validation, expired sessions and malformed success responses reject with actionable messages', async () => {
    for (const [status, response, message] of [
        [422, { errors: { image: ['Ảnh không hợp lệ.'] } }, /Ảnh không hợp lệ/],
        [419, null, /Phiên đăng nhập/],
        [503, null, /Không tải được ảnh/],
        [200, null, /thông tin ảnh hợp lệ/],
        [200, { id: 5 }, /thông tin ảnh hợp lệ/],
    ]) {
        const ui = uploadFixture();
        const pending = ui.upload();
        const rejection = assert.rejects(pending, message);
        await nextTurn();
        Object.assign(ui.requests[0], { status, response });
        ui.requests[0].onload();
        await rejection;
    }
});

test('network failures and timeouts finish with errors instead of an indefinite upload', async () => {
    for (const [event, message] of [['onerror', /kiểm tra mạng/], ['ontimeout', /Tải ảnh quá lâu/]]) {
        const ui = uploadFixture();
        const pending = ui.upload();
        const rejection = assert.rejects(pending, message);
        await nextTurn();
        ui.requests[0][event]();
        await rejection;
    }
});

test('canceling an upload aborts the request and prevents completion from becoming a success', async () => {
    const ui = uploadFixture();
    const pending = ui.upload();
    const rejection = assert.rejects(pending, { name: 'AbortError' });
    await nextTurn();
    ui.controller.abort();
    await rejection;
});

test('canceling before upload never starts a request', async () => {
    const ui = uploadFixture();
    ui.controller.abort();
    await assert.rejects(ui.upload(), { name: 'AbortError' });
    assert.equal(ui.requests.length, 0);
});
