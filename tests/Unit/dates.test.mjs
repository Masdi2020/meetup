import assert from 'node:assert/strict';
import { test } from 'node:test';
import { bookingDateForInput, localDateString } from '../../resources/js/lib/dates.ts';

test('uses the local date near midnight instead of the UTC date', () => {
    process.env.TZ = 'Asia/Makassar';
    const date = new Date('2026-09-02T00:15:00+08:00');
    assert.equal(localDateString(date), '2026-09-02');
});

test('converts booking dates for date inputs and rejects unsupported formats', () => {
    assert.equal(bookingDateForInput('02/09/2026'), '2026-09-02');
    assert.equal(bookingDateForInput('2026-09-02'), '2026-09-02');
    assert.equal(bookingDateForInput(''), '');
    assert.equal(bookingDateForInput('September 2, 2026'), '');
    assert.equal(bookingDateForInput('2026-09-02extra'), '');
});
