import test from 'node:test';
import assert from 'node:assert/strict';
import { ownershipWarning } from '../../resources/js/ownership-confirmation.js';

test('family confirmation identifies the selected person and relationship', () => {
    const message = ownershipWarning('Sample Resident', 'Wife');
    assert.match(message, /Sample Resident \(Wife\)/);
    assert.doesNotMatch(message, /not listed as family/);
});

test('non-family and unspecified relationships receive an extra warning', () => {
    for (const relation of ['Friend', 'Tenant', 'Helper', 'Unspecified', 'Other']) {
        assert.match(ownershipWarning('Sample Resident', relation), /not listed as family/);
    }
});
