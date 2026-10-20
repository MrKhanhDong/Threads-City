import test from 'node:test';
import assert from 'node:assert/strict';
import { plainComposerHtml } from '../../resources/js/thread-editor.js';

test('legacy drafts keep literal tags, quotes and line breaks when migrating to formatted editing', () => {
    assert.equal(plainComposerHtml('<script>alert("hi")</script>\n& nội dung'), '<p>&lt;script&gt;alert(&quot;hi&quot;)&lt;/script&gt;<br>&amp; nội dung</p>');
    assert.equal(plainComposerHtml('đầu\r\nsau\rcuối'), '<p>đầu<br>sau<br>cuối</p>');
    assert.equal(plainComposerHtml(''), '');
});
