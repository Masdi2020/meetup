export interface NamedEntity {
    id: number;
    name: string;
}

export type FacilitySummary = NamedEntity;
export type RoomSummary = NamedEntity;

export interface RoomDetails extends RoomSummary {
    capacity: number;
    location: string;
    facilities: FacilitySummary[];
    image_path: string | null;
}

export interface BookingRoom extends RoomDetails {
    has_display: boolean;
}

export interface AvailabilityRoom extends RoomDetails {
    calendar_url: string;
}

export interface DisplayRoom extends RoomSummary {
    location: string;
    capacity: number;
    is_available: boolean;
    has_display: boolean;
}
