export interface CalendarEvent {
    id: number;
    title: string;
    status: 'PENDING' | 'APPROVED' | 'FINISHED';
    room: string;
    date: string;
    start_time: string;
    end_time: string;
    borrower: string;
    participants_count: number;
    notes: string | null;
}
