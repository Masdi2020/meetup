import type { BookingStatusCode } from './booking';

export interface CalendarEvent {
    id: number;
    title: string;
    status: Extract<BookingStatusCode, 'PENDING' | 'APPROVED' | 'FINISHED'>;
    room: string;
    date: string;
    start_time: string;
    end_time: string;
    borrower: string;
    participants_count: number;
    notes: string | null;
}
