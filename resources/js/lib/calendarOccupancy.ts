import type { CalendarEvent } from '../types/calendar';

export function calendarOccupancy(
    events: CalendarEvent[],
    roomIds: number[],
): 'empty' | 'partial' | 'full' {
    const selectedEvents = events.filter((event) =>
        roomIds.includes(event.room_id),
    );

    if (!selectedEvents.length) {
        return 'empty';
    }

    const full = roomIds.every((roomId) => {
        const bookings = selectedEvents
            .filter(
                (event) =>
                    event.room_id === roomId && event.status !== 'PENDING',
            )
            .sort((a, b) => a.start_time.localeCompare(b.start_time));
        let coveredUntil = '07:30';

        for (const booking of bookings) {
            if (booking.start_time > coveredUntil) {
                break;
            }

            if (booking.end_time > coveredUntil) {
                coveredUntil = booking.end_time;
            }

            if (coveredUntil >= '16:00') {
                return true;
            }
        }

        return false;
    });

    return full ? 'full' : 'partial';
}
