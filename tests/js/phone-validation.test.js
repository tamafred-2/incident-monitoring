import test from 'node:test';
import assert from 'node:assert/strict';
import { phoneDigits, validPhone } from '../../resources/js/phone-validation.js';

test('phone input removes non-digits and preserves leading zero', () => {
    assert.equal(phoneDigits('09ab12 345-6789'), '09123456789');
    assert.equal(phoneDigits('letters'), '');
    assert.equal(phoneDigits('091234567890'), '09123456789');
});

test('only exactly eleven ASCII digits are valid', () => {
    assert.equal(validPhone('09123456789'), true);
    for (const value of ['', '0912345678', '091234567890', '0912345678a', '+9123456789', '09123 56789']) {
        assert.equal(validPhone(value), false, value);
    }
});
