import assert from 'node:assert/strict';
import test from 'node:test';
import { recommendEuropeanSize } from '../../resources/js/lib/shoeSize.ts';

test('shoe size helper selects the nearest displayed EU size within the chart', () => {
    assert.equal(recommendEuropeanSize(22.5), 36);
    assert.equal(recommendEuropeanSize(25.1), 40);
    assert.equal(recommendEuropeanSize(28.5), 45);
    assert.equal(recommendEuropeanSize(23.5), 38);
});

test('shoe size helper uses the roomier size on a boundary and rejects out of range measurements', () => {
    assert.equal(recommendEuropeanSize(22.8), 37);
    assert.equal(recommendEuropeanSize(22.4), null);
    assert.equal(recommendEuropeanSize(28.6), null);
    assert.equal(recommendEuropeanSize(Number.NaN), null);
});
