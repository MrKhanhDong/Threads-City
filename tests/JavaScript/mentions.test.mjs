import test from 'node:test';
import assert from 'node:assert/strict';
import { mentionAtCaret, fetchMentionUsers, setupEditorMentions } from '../../resources/js/mentions.js';

test('mention suggestions start at a word boundary and leave email addresses and completed words alone', () => {
    assert.deepEqual(mentionAtCaret('Xin chào @minh', 14), { query: 'minh', start: 9, end: 14 });
    assert.deepEqual(mentionAtCaret('@', 1), { query: '', start: 0, end: 1 });
    assert.equal(mentionAtCaret('mail@minh.com', 9), null);
    assert.equal(mentionAtCaret('@minh ', 6), null);
    assert.equal(mentionAtCaret('hello', 5), null);
    assert.deepEqual(mentionAtCaret('@minh-anh', 9), { query: 'minh-anh', start: 0, end: 9 });
});

test('suggestions use uncached authenticated JSON and reject malformed or external profile links', async () => {
    const result = await fetchMentionUsers('https://threads.test/mentions/suggestions', 'minh', async (url, options) => {
        assert.equal(url.searchParams.get('q'), 'minh');
        assert.equal(options.cache, 'no-store');
        assert.equal(options.credentials, 'same-origin');
        return { ok: true, json: async () => ({ users: [
            { id: 3, handle: '@minh', name: '<b>Minh</b>', url: 'https://threads.test/people/3' },
            { id: 4, handle: '@other', name: 'Other', url: 'https://evil.test/people/4' },
            { id: 5, handle: '@bad', name: 'Bad', url: 'javascript:alert(1)' }, null,
        ] }) };
    });
    assert.equal(result.length, 1);
    assert.equal(result[0].id, 3);
    await assert.rejects(fetchMentionUsers('https://threads.test/mentions/suggestions', '', async () => ({ ok: false, json: async () => ({}) })), /gợi ý/);
});

test('TinyMCE integration inserts a stable profile link only after selecting a suggestion', () => {
    let options, range, inserted, saved = false, hidden = false;
    const editor = {
        ui: { registry: { addAutocompleter(name, value) { assert.equal(name, 'people'); options = value; } } },
        on() {},
        getDoc: () => ({ createElement: () => ({ href: '', textContent: '', title: '', get outerHTML() { return `<a href="${this.href}">${this.textContent}</a>`; } }) }),
        selection: { setRng(value) { range = value; } }, insertContent(value) { inserted = value; }, save() { saved = true; },
    };
    setupEditorMentions(editor, 'https://threads.test/mentions/suggestions');
    assert.equal(options.trigger, '@');
    assert.equal(inserted, undefined);
    options.onAction({ hide() { hidden = true; } }, 'caret', JSON.stringify({ id: 7, url: 'https://threads.test/people/7', handle: '@minh', name: 'Minh' }));
    assert.equal(range, 'caret'); assert.match(inserted, /people\/7/); assert.equal(saved, true); assert.equal(hidden, true);
});
