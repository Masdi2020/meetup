import type { BookingStatusCode } from '@/types/booking';

export const eventStatusLabels: Record<BookingStatusCode, string> = {
    PENDING: 'Pending',
    APPROVED: 'Disetujui',
    FINISHED: 'Selesai',
    REJECTED: 'Ditolak',
    CANCELLED: 'Dibatalkan',
};
