import test from 'node:test';
import assert from 'node:assert/strict';
import { nativeValidationMessage, requestErrorMessage, setupVietnameseValidation } from '../../resources/js/validation-messages.js';

const input = overrides => ({
    type: 'text', name: 'name', value: '', labels: [{ textContent: 'Tên hiển thị' }],
    validity: { valid: true }, ...overrides,
});

test('required and checkbox errors identify what to enter or confirm', () => {
    assert.equal(nativeValidationMessage(input({ validity: { valueMissing: true } })), 'Vui lòng nhập hoặc chọn Tên hiển thị.');
    assert.equal(nativeValidationMessage(input({ type: 'checkbox', validity: { valueMissing: true } })), 'Vui lòng tích ô xác nhận trước khi tiếp tục.');
});

test('email and length errors explain the required format and limit', () => {
    assert.match(nativeValidationMessage(input({ type: 'email', labels: [{ textContent: 'Email' }], validity: { typeMismatch: true } })), /Email không đúng định dạng/);
    assert.match(nativeValidationMessage(input({ minLength: 8, validity: { tooShort: true } })), /ít nhất 8 ký tự/);
    assert.match(nativeValidationMessage(input({ maxLength: 100, validity: { tooLong: true } })), /dài quá 100 ký tự/);
});

test('invalid numbers and patterns are Vietnamese while valid input has no error', () => {
    assert.equal(nativeValidationMessage(input()), '');
    assert.match(nativeValidationMessage(input({ validity: { badInput: true } })), /số đúng định dạng/);
    assert.match(nativeValidationMessage(input({ validity: { patternMismatch: true } })), /không đúng định dạng/);
    assert.match(nativeValidationMessage(input({ min: '1', validity: { rangeUnderflow: true } })), /lớn hơn hoặc bằng 1/);
    assert.match(nativeValidationMessage(input({ max: '90', validity: { rangeOverflow: true } })), /không được lớn hơn 90/);
});

test('password confirmation names the mismatch and accepts identical values', () => {
    const confirmation = input({ name: 'password_confirmation', value: 'different', form: { elements: { namedItem: () => ({ value: 'Password123' }) } } });
    assert.match(nativeValidationMessage(confirmation), /Mật khẩu xác nhận không khớp/);
    confirmation.value = 'Password123';
    assert.equal(nativeValidationMessage(confirmation), '');
});

test('network and parser failures are translated without exposing unknown errors', () => {
    assert.match(requestErrorMessage(new TypeError('Failed to fetch'), 'Thử lại.'), /Không kết nối được máy chủ/);
    assert.match(requestErrorMessage(new SyntaxError('Unexpected token'), 'Thử lại.'), /không đúng định dạng/);
    assert.equal(requestErrorMessage(new Error('Ảnh vượt quá giới hạn 5 MB.'), 'Thử lại.'), 'Ảnh vượt quá giới hạn 5 MB.');
    assert.equal(requestErrorMessage(new Error('internal stack details'), 'Thao tác chưa hoàn tất.'), 'Thao tác chưa hoàn tất.');
});

test('input listeners clear stale errors so valid data can be submitted again', () => {
    const listeners = new Map();
    setupVietnameseValidation({ addEventListener: (name, listener) => listeners.set(name, listener) });
    const messages = [];
    const control = input({ validity: { valueMissing: true }, setCustomValidity: value => messages.push(value) });
    listeners.get('invalid')({ target: control });
    assert.deepEqual(messages, ['', 'Vui lòng nhập hoặc chọn Tên hiển thị.']);
    listeners.get('input')({ target: control });
    assert.equal(messages.at(-1), '');
    listeners.get('change')({ target: control });
    assert.equal(messages.at(-1), '');
    assert.doesNotThrow(() => listeners.get('input')({ target: {} }));
});
