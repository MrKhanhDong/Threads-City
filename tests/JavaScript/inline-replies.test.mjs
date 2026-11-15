import test from 'node:test';
import assert from 'node:assert/strict';
import { replyValidation, setupInlineReplies } from '../../resources/js/inline-replies.js';
import { syncRegion } from '../../resources/js/live-updates.js';

test('inline replies allow text or attached photos and retain validation until uploads finish', () => {
    assert.equal(replyValidation('Chào bạn', 0, 0), '');
    assert.equal(replyValidation('', 1, 0), '');
    assert.match(replyValidation('   ', 0, 0), /Nhập nội dung/);
    assert.match(replyValidation('Chào', 1, 1), /Chờ ảnh/);
    assert.equal(replyValidation('😀'.repeat(2000), 0, 0), '');
    assert.match(replyValidation('😀'.repeat(2001), 0, 0), /2.000/);
});

test('live updates preserve the inline composer and unsent draft under its target', () => {
    let replaced = false;
    const form = { content: 'Bản nháp đang viết' };
    const current = {
        outerHTML: 'Thread with inline reply and draft',
        hasAttribute: () => false,
        querySelectorAll: () => [],
        querySelector: selector => selector === '[data-inline-reply-form]' ? form : null,
        replaceWith() { replaced = true; },
    };
    syncRegion(current, { outerHTML: 'Thread with new statistics', querySelectorAll: () => [] }, []);
    assert.equal(replaced, false);
    assert.equal(form.content, 'Bản nháp đang viết');
});

test('guest pages without the composer template keep normal reply navigation', () => {
    setupInlineReplies({ getElementById: () => null, addEventListener() { assert.fail('No event listeners expected'); } }, {});
});

test('reply opens beneath its own comment with the correct parent and preserves the draft on reopen', () => {
    const listeners = new Map();
    const input = { value: '', focus() {} };
    const recipient = {}, context = {};
    let mounted = null, clones = 0;
    const form = {
        elements: { parent_id: { value: '' } }, hidden: true,
        querySelector: selector => ({ '[data-reply-recipient]': recipient, '[data-reply-context]': context, '[data-reply-input]': input })[selector],
        scrollIntoView() {},
    };
    const slot = { querySelector: () => mounted, append(node) { mounted = node; } };
    const card = { dataset: { threadParent: '1' }, querySelector: selector => selector === '[data-inline-reply-slot]' ? slot : { textContent: 'Bình luận mà tôi muốn trả lời' } };
    const trigger = {
        dataset: { inlineReply: '42', replyAuthor: '@nguoi_viet' }, attributes: {},
        closest(selector) { return selector === '[data-inline-reply]' ? this : selector === '[data-thread-id]' ? card : null; },
        setAttribute(name, value) { this.attributes[name] = value; },
    };
    const doc = {
        getElementById: () => ({ content: { firstElementChild: { cloneNode() { clones++; return form; } } } }),
        querySelector: selector => selector === 'meta[name="csrf-token"]' ? { content: 'test-csrf' } : null,
        addEventListener(type, listener) { listeners.set(type, listener); },
    };
    setupInlineReplies(doc, { matchMedia: () => ({ matches: true }) });
    listeners.get('click')({ target: trigger, preventDefault() {} });
    assert.equal(mounted, form);
    assert.equal(form.elements.parent_id.value, '42');
    assert.equal(recipient.textContent, '@nguoi_viet');
    assert.equal(context.textContent, 'Bình luận mà tôi muốn trả lời');
    assert.equal(trigger.attributes['aria-controls'], form.id);
    input.value = 'Câu trả lời đang viết'; form.hidden = true;
    listeners.get('click')({ target: trigger, preventDefault() {} });
    assert.equal(clones, 1);
    assert.equal(form.hidden, false);
    assert.equal(input.value, 'Câu trả lời đang viết');
});

function submitFixture({ detail = false, hash = '' } = {}) {
    const listeners = new Map();
    const input = { value: '', style: {}, scrollHeight: 30, disabled: false, focusCalls: 0, focus() { this.focusCalls++; } };
    const sendButton = { disabled: false };
    const output = { textContent: '', isError: false, classList: { toggle(name, value) { output.isError = value; } } };
    const inserted = [];
    const results = { querySelector: () => null, insertAdjacentHTML(position, html) { inserted.push(html); } };
    const count = { dataset: { replyCount: '0' }, textContent: '' };
    const preview = { replaceChildren() {} };
    let mounted;
    let mountedAt;
    const slot = { querySelector: () => mounted, append(node) { mounted = node; } };
    const detailSlot = { querySelector: () => mounted, append(node) { mounted = node; mountedAt = 'detail'; } };
    const card = { dataset: { threadParent: '1' }, querySelector: selector => ({ '[data-inline-reply-slot]': slot, '.thread-content-text': { textContent: 'Bình luận gốc' }, '[data-inline-reply-results]': results, '[data-reply-count]': count })[selector] };
    let activeCard = card;
    const total = { textContent: '0' };
    const prompt = { hidden: false, focusCalls: 0, focus() { this.focusCalls++; }, closest: selector => selector === '[data-thread-detail-prompt]' ? prompt : null };
    const home = { dataset: { replyHome: '42' }, querySelector: selector => ({ '[data-thread-detail-composer]': detailSlot, '[data-thread-detail-prompt]': prompt })[selector] };
    const trigger = {
        dataset: { inlineReply: '42', replyAuthor: '@nguoi_viet' }, attributes: {}, focusCalls: 0,
        closest(selector) { return selector === '[data-inline-reply]' ? this : selector === '[data-thread-id]' ? activeCard : null; },
        setAttribute(name, value) { this.attributes[name] = value; }, focus() { this.focusCalls++; },
    };
    const form = {
        action: '/threads', hidden: true, dataset: {}, elements: { parent_id: { value: '' }, content: input },
        closest: selector => selector === '[data-inline-reply-form]' ? form : selector === '[data-thread-id]' && !detail ? card : null,
        querySelector: selector => ({ '[data-reply-recipient]': {}, '[data-reply-context]': {}, '[data-reply-input]': input, '[data-reply-media]': preview, '[data-reply-status]': output })[selector],
        querySelectorAll: selector => selector === 'button, textarea' ? [sendButton, input] : [], scrollIntoView() {},
    };
    const doc = {
        getElementById: () => ({ content: { firstElementChild: { cloneNode: () => form } } }),
        querySelector: selector => ({
            'meta[name="csrf-token"]': { content: 'test-csrf' },
            '[data-reply-home]': detail ? home : null,
            '[data-live-region="thread"] [data-inline-reply="42"]': detail ? trigger : null,
            '[data-live-region="thread"] [data-thread-url]': detail ? activeCard : null,
            '[data-live-region="replies"]': detail ? results : null,
            '[data-thread-replies-total]': detail ? total : null,
        })[selector] || null,
        addEventListener(type, listener) { listeners.set(type, listener); }, dispatchEvent() {},
    };
    const win = {
        matchMedia: () => ({ matches: true }), dispatchEvent() {}, location: { hash },
        FormData: class extends FormData { constructor(target) { super(); this.set('parent_id', target.elements.parent_id.value); this.set('type', 'reply'); } },
    };
    setupInlineReplies(doc, win);
    if (!detail) listeners.get('click')({ target: trigger, preventDefault() {} });
    input.value = 'Câu trả lời mới';
    return { form, input, trigger, output, inserted, count, total, prompt, sendButton, mountedAt,
        replaceCard(node) { activeCard = node; },
        openPrompt: () => listeners.get('click')({ target: prompt, preventDefault() {} }),
        submit: () => listeners.get('submit')({ target: form, preventDefault() {} }),
    };
}

test('detail replies mount below the post without moving keyboard focus or scrolling away from the post', () => {
    const ui = submitFixture({ detail: true });
    assert.equal(ui.mountedAt, 'detail');
    assert.equal(ui.form.closest('[data-thread-id]'), null);
    assert.equal(ui.form.dataset.detailReply, '1');
    assert.equal(ui.form.hidden, false);
    assert.equal(ui.prompt.hidden, true);
    assert.equal(ui.input.focusCalls, 0);
    assert.equal(ui.input.placeholder, 'Trả lời @nguoi_viet…');
});

test('detail submission stays attached to the post after live refresh and returns to a compact reply prompt', async context => {
    const ui = submitFixture({ detail: true });
    const refreshedCount = { dataset: { replyCount: '5' }, textContent: '5' };
    ui.replaceCard({ querySelector: selector => selector === '[data-reply-count]' ? refreshedCount : null });
    context.mock.method(globalThis, 'fetch', async () => new Response(JSON.stringify({ success: true, html: '<article>Bình luận mới</article>' }), { status: 200 }));
    await ui.submit();
    assert.deepEqual(ui.inserted, ['<article>Bình luận mới</article>']);
    assert.equal(refreshedCount.dataset.replyCount, '6');
    assert.equal(ui.total.textContent, '1');
    assert.equal(ui.form.hidden, true);
    assert.equal(ui.prompt.hidden, false);
    assert.equal(ui.prompt.focusCalls, 1);
    ui.openPrompt();
    assert.equal(ui.form.hidden, false);
    assert.equal(ui.prompt.hidden, true);
});

test('successful submission closes the composer, keeps the new comment and restores focus to its reply button', async context => {
    const ui = submitFixture();
    context.mock.method(globalThis, 'fetch', async (url, options) => {
        assert.equal(options.body.get('parent_id'), '42');
        assert.equal(options.body.get('content'), 'Câu trả lời mới');
        return new Response(JSON.stringify({ success: true, html: '<article>Đã gửi</article>' }), { status: 200 });
    });
    await ui.submit();
    assert.equal(ui.form.hidden, true);
    assert.equal(ui.input.value, '');
    assert.equal(ui.trigger.attributes['aria-expanded'], 'false');
    assert.equal(ui.trigger.focusCalls, 1);
    assert.equal(ui.input.focusCalls, 1);
    assert.deepEqual(ui.inserted, ['<article>Đã gửi</article>']);
    assert.equal(ui.count.dataset.replyCount, '1');
    assert.equal(ui.sendButton.disabled, false);
});

test('the comment icon on a feed post keeps its detail link instead of opening an inline form', () => {
    const listeners = new Map();
    const card = { dataset: { threadParent: '' } };
    const trigger = { closest: selector => selector === '[data-inline-reply]' ? trigger : card };
    const doc = {
        getElementById: () => ({ content: { firstElementChild: { cloneNode() { assert.fail('Feed posts must navigate to their detail page'); } } } }),
        querySelector: selector => selector === 'meta[name="csrf-token"]' ? { content: 'test' } : null,
        addEventListener(type, listener) { listeners.set(type, listener); },
    };
    setupInlineReplies(doc, {});
    let prevented = false;
    listeners.get('click')({ target: trigger, preventDefault() { prevented = true; } });
    assert.equal(prevented, false);
});

for (const response of [
    { status: 422, data: { errors: { content: ['Không gửi được bình luận.'] } } },
    { status: 200, data: { success: false } },
]) {
    test(`unconfirmed submission (${response.status}) keeps the composer open and preserves the draft`, async context => {
        const ui = submitFixture();
        context.mock.method(globalThis, 'fetch', async () => new Response(JSON.stringify(response.data), { status: response.status }));
        await ui.submit();
        assert.equal(ui.form.hidden, false);
        assert.equal(ui.input.value, 'Câu trả lời mới');
        assert.equal(ui.output.isError, true);
        assert.equal(ui.trigger.attributes['aria-expanded'], 'true');
        assert.equal(ui.trigger.focusCalls, 0);
        assert.equal(ui.input.focusCalls, 2);
        assert.deepEqual(ui.inserted, []);
        assert.equal(ui.sendButton.disabled, false);
    });
}
