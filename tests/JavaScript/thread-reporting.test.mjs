import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function fixture(response) {
    const button = { textContent: 'Gửi báo cáo', addEventListener(name, callback) { this.click = () => callback.call(this); } };
    const modal = {}, messages = [];
    let hidden = false, sent;
    const source = readFileSync(new URL('../../resources/views/threads/partials/page_actions.blade.php', import.meta.url), 'utf8');
    const script = source.slice(source.indexOf('    // Report modal submit'), source.indexOf('    // 4.10'));
    vm.runInNewContext(script, {
        document: {
            getElementById: id => ({ btnSubmitReport: button, reportModalThreadId: { value: '12' }, reportThreadModal: modal })[id],
            querySelector: () => ({ value: 'spam' }),
        },
        window: { bootstrap: {} }, bootstrap: { Modal: { getInstance: () => ({ hide() { hidden = true; } }) } },
        csrfToken: 'csrf', showToast: message => messages.push(message),
        fetch: async (url, options) => { sent = { url, options }; return response; },
    });
    return { button, messages, hidden: () => hidden, sent: () => sent };
}

test('successful reports close the modal only after confirmation from the server', async () => {
    const ui = fixture({ ok: true, json: async () => ({ success: true, message: 'Đã gửi báo cáo' }) });
    const pending = ui.button.click();
    assert.equal(ui.button.disabled, true);
    assert.equal(ui.hidden(), false);
    await pending;
    assert.equal(ui.sent().url, '/threads/12/report');
    assert.equal(ui.hidden(), true);
    assert.equal(ui.button.disabled, false);
    assert.equal(ui.messages[0], 'Đã gửi báo cáo');
});

test('failed reports leave the modal open and show the actual error with retry enabled', async () => {
    for (const response of [
        { ok: false, status: 422, json: async () => ({ errors: { reason: ['Lý do không hợp lệ'] } }) },
        { ok: false, status: 500, json: async () => { throw new Error('HTML response'); } },
    ]) {
        const ui = fixture(response);
        await ui.button.click();
        assert.equal(ui.hidden(), false);
        assert.equal(ui.button.disabled, false);
        assert.equal(ui.button.textContent, 'Gửi báo cáo');
        assert.match(ui.messages[0], /không hợp lệ|Không gửi được/);
    }
});
