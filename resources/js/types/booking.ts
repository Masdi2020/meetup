export type BookingHistoryStatus =
    'Approved' | 'Pending' | 'Rejected' | 'Cancelled' | 'Finished';

export interface BookingHistory {
    id: number;
    room: string;
    date: string;
    time: string;
    title: string;
    status: BookingHistoryStatus;
    results_available: boolean;
    documentations: BookingResultAttachment[];
    meeting_minutes: BookingResultAttachment | null;
}

export interface BookingResultFile {
    id: number;
    original_filename: string;
    path: string;
}

export interface BookingResultAttachment extends BookingResultFile {
    mime_type: string;
}

export interface HistoryStatusOption {
    value: BookingHistoryStatus;
    label: string;
}

export interface BookingAttachment {
    path: string;
}

export interface BannerBooking {
    title: string;
    start_time: string;
    end_time: string;
    attachments: BookingAttachment[];
}

export interface BannerPageProps {
    booking: BannerBooking | null;
    next_change: string | null;
    now: string;
}
