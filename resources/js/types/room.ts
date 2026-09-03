export interface NamedEntity {
    id: number;
    name: string;
}

export type FacilitySummary = NamedEntity;
export type RoomSummary = NamedEntity;

export interface BookingRoom extends RoomSummary {
    has_display: boolean;
}

export interface AvailabilityRoom extends RoomSummary {
    capacity: number;
    location: string;
    calendar_url: string;
    facilities: FacilitySummary[];
    image: string | null;
}

export interface DisplayRoom extends RoomSummary {
    location: string;
    capacity: number;
    is_available: boolean;
    has_display: boolean;
}
