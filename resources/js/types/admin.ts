import type { UserRole } from './auth';
import type { BookingResultFile } from './booking';
import type { NamedEntity } from './room';

export type BookingStatus =
    'pending' | 'approved' | 'rejected' | 'cancelled' | 'finished';

export interface AdminUser {
    id: number;
    name: string;
    username: string;
    role: UserRole;
}

export type RoomOption = NamedEntity;
export type FacilityOption = NamedEntity;

export interface BorrowerOption extends NamedEntity {
    role: Extract<UserRole, 'user' | 'admin'>;
}

export interface AdminBookingStatusOption {
    code: BookingStatus;
    label: string;
}

export interface AdminRoom extends RoomOption {
    location: string;
    capacity: number;
    is_available: boolean;
    facilities: string[];
    facility_ids: number[];
}

export interface Facility extends FacilityOption {
    rooms_count: number;
    rooms: RoomOption[];
}

export interface AdminBooking {
    id: number;
    room_id: number;
    room: string;
    borrower: string;
    activity: string;
    date: string;
    start: string;
    end: string;
    status: BookingStatus;
    request?: string;
    processed_notes?: string;
    documentations: BookingResultFile[];
    meeting_minutes: BookingResultFile | null;
}

export interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
export interface AuditUser {
    id: number;
    name: string;
    role: UserRole | string;
}

export interface AuditLog {
    id: number;
    entity_type: string | null;
    entity_id: number | null;
    action: string;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    changed_by?: number | null;
    ip_address: string | null;
    comment: string | null;
    created_at: string;
    user: AuditUser | null;
}

export interface PaginatedAudits extends Pagination<AuditLog> {
    per_page: number;
    total: number;
    links: PaginationLink[];
}

export interface BookingStats {
    total: number;
    pending: number;
    approved: number;
    finished: number;
}
export interface AuditStats {
    total: number;
    today: number;
    admin: number;
    user: number;
}
export interface DashboardStats {
    rooms: number;
    users: number;
    bookings: number;
    pending: number;
}

export interface TodayBooking {
    id: number;
    title: string;
    start_time: string;
    room: RoomOption;
}
