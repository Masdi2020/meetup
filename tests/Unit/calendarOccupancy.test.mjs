import assert from 'node:assert/strict';
import { test } from 'node:test';
import { calendarOccupancy } from '../../resources/js/lib/calendarOccupancy.ts';

const booking = (room_id, start_time, end_time, status = 'APPROVED') => ({
    room_id, start_time, end_time, status,
});

test('empty dates and empty room selections stay white', () => {
    assert.equal(calendarOccupancy([], [1]), 'empty');
    assert.equal(calendarOccupancy([booking(1, '07:30', '16:00')], []), 'empty');
    assert.equal(calendarOccupancy([booking(2, '07:30', '16:00')], [1]), 'empty');
});

test('all selected rooms must cover the entire working day to be full', () => {
    const events = [
        booking(1, '12:00', '20:00'),
        booking(2, '07:00', '16:00', 'FINISHED'),
        booking(1, '07:30', '12:00'),
    ];
    assert.equal(calendarOccupancy(events, [1, 2]), 'full');
    assert.equal(calendarOccupancy(events, [1, 2, 3]), 'partial');
    assert.equal(calendarOccupancy(events, [1]), 'full');
});

test('overlapping bookings cannot hide a gap in the working day', () => {
    assert.equal(calendarOccupancy([
        booking(1, '07:30', '12:00'),
        booking(1, '08:00', '11:00'),
        booking(1, '12:01', '20:00'),
    ], [1]), 'partial');
    assert.equal(calendarOccupancy([
        booking(1, '07:00', '12:00'),
        booking(1, '11:00', '16:00'),
    ], [1]), 'full');
});

test('pending and evening bookings stay visible without filling the working day', () => {
    assert.equal(calendarOccupancy([booking(1, '07:30', '16:00', 'PENDING')], [1]), 'partial');
    assert.equal(calendarOccupancy([booking(1, '16:00', '22:00')], [1]), 'partial');
    assert.equal(calendarOccupancy([booking(1, '07:31', '16:00')], [1]), 'partial');
    assert.equal(calendarOccupancy([booking(1, '07:30', '15:59')], [1]), 'partial');
});
