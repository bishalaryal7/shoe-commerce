import assert from 'node:assert/strict';
import test from 'node:test';
import { validateContact } from '../../resources/js/composables/validateContact.ts';
const valid = { name: 'EVE Customer', email: 'buyer@example.com', phone: '+977 9800000000', address: 'Kathmandu, Nepal' };
test('valid delivery details have no client errors', () => assert.deepEqual(validateContact(valid, false), {}));
test('invalid fields are identified before an order request', () => {
    for (const [field, value] of Object.entries({name:' ',email:'not-email',phone:'call-me',address:'Here'})) {
        assert.ok(validateContact({...valid,[field]:value}, false)[field]);
    }
});
test('phone boundaries accept formatting and reject too few or too many digits', () => {
    for (const phone of ['980-000-0000', '+977 (980) 0000000', '+९७७ ९८००००००००']) assert.equal(validateContact({...valid,phone}, false).phone, undefined);
    for (const phone of ['12345', '1234567890123456', '++9779800000000']) assert.ok(validateContact({...valid,phone}, false).phone);
});
test('Nepali field feedback is used when the language is Nepali', () => assert.match(validateContact({...valid,phone:'abc'}, true).phone, /फोन/));
