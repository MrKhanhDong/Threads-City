import test from 'node:test';
import assert from 'node:assert/strict';
import { setupThreadNavigation } from '../../resources/js/thread-navigation.js';

function navigationFixture({ interactive = '', selection = '', current = null, id = '42' } = {}) {
    const destinations = [];
    let click;
    const card = { dataset: { threadId: id, threadUrl: `/t/${id}` } };
    const target = { closest(selector) {
        if (selector === '[data-thread-url]') return card;
        return interactive && selector.split(',').map(value => value.trim()).includes(interactive) ? target : null;
    } };
    const doc = {
        addEventListener(type, handler) { assert.equal(type, 'click'); click = handler; },
        querySelector: () => current ? { dataset: { replyHome: current } } : null,
    };
    setupThreadNavigation(doc, { getSelection: () => ({ toString: () => selection }), location: { assign: url => destinations.push(url) } });
    return { destinations, click: options => click({ target, button: 0, ...options }) };
}

test('clicking blank space in a post opens its own detail page', () => {
    const ui = navigationFixture(); ui.click();
    assert.deepEqual(ui.destinations, ['/t/42']);
});

test('the nearest comment card opens that comment rather than its parent post', () => {
    const ui = navigationFixture({ id: '81', current: '42' }); ui.click();
    assert.deepEqual(ui.destinations, ['/t/81']);
});

test('a detail page does not reload itself when its active post is clicked', () => {
    const ui = navigationFixture({ current: '42' }); ui.click();
    assert.deepEqual(ui.destinations, []);
});

test('links, actions, composer fields, galleries, playback and polls keep their own interactions', () => {
    for (const interactive of ['a', 'button', 'input', 'textarea', 'select', 'form', 'summary', 'details', 'video', 'audio', '[contenteditable]', '[role="button"]', '[data-media-gallery]', '[data-player]', '[data-poll-id]', '.threads-inner-quote-card', '.modal', 'dialog']) {
        const ui = navigationFixture({ interactive }); ui.click();
        assert.deepEqual(ui.destinations, [], interactive);
    }
});

test('selecting text and dragging media do not open a detail page', () => {
    const selected = navigationFixture({ selection: 'Đoạn văn đang được sao chép' }); selected.click();
    assert.deepEqual(selected.destinations, []);
    const dragged = navigationFixture(); dragged.click({ defaultPrevented: true });
    assert.deepEqual(dragged.destinations, []);
});

test('modifier clicks and non-primary buttons do not trigger card navigation', () => {
    for (const options of [{ ctrlKey: true }, { metaKey: true }, { shiftKey: true }, { altKey: true }, { button: 1 }, { button: 2 }]) {
        const ui = navigationFixture(); ui.click(options);
        assert.deepEqual(ui.destinations, []);
    }
});
