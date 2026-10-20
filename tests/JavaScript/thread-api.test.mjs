import test from 'node:test';
import assert from 'node:assert/strict';
import { composerValidation, threadRequest } from '../../resources/js/thread-api.js';

test('composer accepts a photo without text and counts Vietnamese and emoji characters', () => {
    assert.equal(composerValidation('', 1), '');
    assert.equal(composerValidation('đ'.repeat(500), 0), '');
    assert.equal(composerValidation('🌿'.repeat(500), 0), '');
    assert.notEqual(composerValidation('🌿'.repeat(501), 0), '');
    assert.notEqual(composerValidation('  ', 0), '');
    assert.notEqual(composerValidation('A post', 5), '');
});

test('a chain cannot submit with an empty or oversized continuation', () => {
    assert.equal(composerValidation('First', 0, ['Second', 'Third']), '');
    assert.notEqual(composerValidation('First', 0, ['  ']), '');
    assert.notEqual(composerValidation('First', 0, ['x'.repeat(501)]), '');
    assert.notEqual(composerValidation('First', 0, Array(10).fill('Next')), '');
});

test('publishing sends JSON, CSRF and same-session credentials to the given route', async () => {
    let received;
    const result = await threadRequest('/threads', {
        method: 'POST', csrf: 'test-csrf', body: { content: 'Hello', media_ids: [7] },
        fetcher: async (url, options) => { received = { url, options }; return Response.json({ success: true, html: '<article>Hi</article>' }, { status: 201 }); }
    });
    assert.equal(received.url, '/threads');
    assert.equal(received.options.credentials, 'same-origin');
    assert.equal(received.options.headers['X-CSRF-TOKEN'], 'test-csrf');
    assert.deepEqual(JSON.parse(received.options.body), { content: 'Hello', media_ids: [7] });
    assert.equal(result.success, true);
});

test('image uploads preserve FormData so the browser supplies the multipart boundary', async () => {
    const upload = new FormData();
    upload.append('image', new Blob(['image'], { type: 'image/jpeg' }), 'test.jpg');
    await threadRequest('/threads/upload-media', { method: 'POST', body: upload, fetcher: async (url, options) => {
        assert.equal(options.body, upload);
        assert.equal(options.headers['Content-Type'], undefined);
        return Response.json({ id: 1, url: '/media/1' }, { status: 201 });
    } });
});

test('validation, network failures and expired sessions are surfaced as failures', async () => {
    await assert.rejects(threadRequest('/threads', { fetcher: async () => Response.json({ errors: { content: ['Tối đa 500 ký tự.'] } }, { status: 422 }) }), /Tối đa 500 ký tự/);
    await assert.rejects(threadRequest('/threads', { fetcher: async () => { throw new TypeError('offline'); } }), /Nội dung của bạn vẫn được giữ lại/);
    await assert.rejects(threadRequest('/threads', { fetcher: async () => Response.json({}, { status: 419 }) }), /Phiên đã hết hạn/);
    await assert.rejects(threadRequest('/threads', { fetcher: async () => new Response('<html>Login</html>') }), /Phiên làm việc/);
});
