import test from 'node:test';
import assert from 'node:assert/strict';
import { chatError, insertEmoji, recordingType, setupChat } from '../../resources/js/chat.js';

test('chat surfaces field errors and an expired session without losing the draft', () => {
    assert.equal(chatError({ errors: { image: ['Ảnh quá lớn.'] } }, 422), 'Ảnh quá lớn.');
    assert.match(chatError({}, 419), /Phiên đăng nhập/);
    assert.match(chatError({}, 401), /đăng nhập lại/);
    assert.equal(chatError({ message: 'Không có quyền.' }, 404), 'Không có quyền.');
});

test('emoji replaces the selected text and restores the caret after the complete emoji', () => {
    assert.deepEqual(insertEmoji('Chào bạn', '👋', 5, 8), { value: 'Chào 👋', caret: 7 });
    assert.deepEqual(insertEmoji('Hi', '❤️', 2), { value: 'Hi❤️', caret: 4 });
});

test('voice recorder selects the first supported audio container including Safari MP4', () => {
    assert.equal(recordingType({ isTypeSupported: type => type === 'audio/mp4' }), 'audio/mp4');
    assert.equal(recordingType({ isTypeSupported: () => true }), 'audio/webm;codecs=opus');
    assert.equal(recordingType({ isTypeSupported: () => false }), undefined);
});

test('pages without chat controls do not make network calls or start timers', () => {
    setupChat({ querySelector: () => null }, { fetch() { assert.fail('Unexpected network request'); } });
});
