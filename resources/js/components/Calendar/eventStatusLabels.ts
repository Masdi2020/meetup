import type { CalendarEvent } from '@/types/calendar';

export const eventStatusLabels: Record<CalendarEvent['status'], string> = {
    PENDING: 'Pending',
    APPROVED: 'Disetujui',
    FINISHED: 'Selesai',
};
