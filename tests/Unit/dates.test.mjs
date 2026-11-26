import assert from 'node:assert/strict';
import { test } from 'node:test';
import { bookingDateForInput, localDateString, remainingBookingTime } from '../../resources/js/lib/dates.ts';

test('uses the local date near midnight instead of the UTC date', () => {
    process.env.TZ = 'Asia/Makassar';
    const date = new Date('2026-09-02T00:15:00+08:00');
    assert.equal(localDateString(date), '2026-09-02');
});

test('counts down to the server timestamp across browser time zones and UTC date boundaries', () => {
    const originalTimezone = process.env.TZ;

    try {
        for (const timezone of ['Asia/Makassar', 'UTC', 'America/New_York']) {
            process.env.TZ = timezone;
            assert.equal(remainingBookingTime('2026-11-26T08:00:00+08:00', new Date('2026-11-26T07:15:00+08:00')), '45 menit 0 detik');
            assert.equal(remainingBookingTime('2026-11-26T09:00:00+08:00', new Date('2026-11-26T07:15:00+08:00')), '1 jam 45 menit');
            assert.equal(remainingBookingTime('2026-11-26T08:00:00+08:00', new Date('2026-11-26T08:00:00+08:00')), 'Selesai');
        }
    } finally {
        if (originalTimezone === undefined) {
            delete process.env.TZ;
        } else {
            process.env.TZ = originalTimezone;
        }
    }
});

test('converts booking dates for date inputs and rejects unsupported formats', () => {
    assert.equal(bookingDateForInput('02/09/2026'), '2026-09-02');
    assert.equal(bookingDateForInput('2026-09-02'), '2026-09-02');
    assert.equal(bookingDateForInput(''), '');
    assert.equal(bookingDateForInput('September 2, 2026'), '');
    assert.equal(bookingDateForInput('2026-09-02extra'), '');
});
