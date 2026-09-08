<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Calendar from '@/components/Calendar/Calendar.vue';
import { getRoomColor } from '@/components/Calendar/roomColors';
import { useBookingPageUpdates } from '@/composables/useBookingUpdates';
import type { CalendarEvent } from '@/types/calendar';
import type { AvailabilityRoom } from '@/types/room';

const props = defineProps<{
    rooms: AvailabilityRoom[];
    events: CalendarEvent[];
    month: number;
    year: number;
    date: string;
    view: 'month' | 'week' | 'day';
    selectedRoomIds: number[];
}>();

const rooms = props.rooms;

useBookingPageUpdates(['events']);

const selectedRoomIds = ref<number[]>([...props.selectedRoomIds]);
const filteredRooms = computed(() =>
    rooms.filter((room) => selectedRoomIds.value.includes(room.id)),
);
const allRoomsSelected = computed(
    () => rooms.length > 0 && selectedRoomIds.value.length === rooms.length,
);
const roomFilter = computed(() =>
    selectedRoomIds.value.length ? selectedRoomIds.value : [0],
);

const selectedRoom = computed(() =>
    filteredRooms.value.length === 1 ? filteredRooms.value[0] : null,
);

function toggleAllRooms() {
    selectedRoomIds.value = allRoomsSelected.value
        ? []
        : rooms.map((room) => room.id);
}

function booking(roomId: number) {
    localStorage.setItem('booking_room_id', String(roomId));

    router.get('/booking');
}

function loadCalendar(date: string, view = props.view) {
    router.get(
        '/availability',
        { room: roomFilter.value, date, view },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['events', 'month', 'year', 'date', 'view'],
        },
    );
}

function changeView(view: 'month' | 'week' | 'day') {
    loadCalendar(props.date, view);
}
watch(selectedRoomIds, () => {
    router.get(
        '/availability',
        {
            room: roomFilter.value,
            date: props.date,
            view: props.view,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['events', 'selectedRoomIds'],
        },
    );
});
</script>

<template>
    <div class="app-page availability">
        <header class="page-header page-heading">
            <h1 class="page-title">Ketersediaan Ruangan</h1>
        </header>

        <div class="page-card ui-card ui-card-body">
            <fieldset class="toolbar">
                <legend class="room-filter-heading">
                    <span>Pilih Ruangan</span>
                    <button
                        type="button"
                        class="text-sm font-medium text-blue-600 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!rooms.length"
                        @click="toggleAllRooms"
                    >
                        {{
                            allRoomsSelected ? 'Batalkan semua' : 'Pilih semua'
                        }}
                    </button>
                </legend>
                <div class="room-options">
                    <label
                        v-for="room in rooms"
                        :key="room.id"
                        class="room-option"
                    >
                        <input
                            v-model="selectedRoomIds"
                            type="checkbox"
                            :value="room.id"
                        />
                        {{ room.name }}
                    </label>
                </div>
            </fieldset>

            <div class="room-card" v-if="selectedRoom">
                <div class="room-image">
                    <img
                        v-if="selectedRoom.image"
                        :src="`/storage/${selectedRoom.image}`"
                        :alt="selectedRoom.name"
                    />
                    <span v-else>🖼️</span>
                </div>

                <div class="room-info">
                    <h3>{{ selectedRoom.name }}</h3>

                    <div class="meta">
                        <span>👥 {{ selectedRoom.capacity }} orang</span>
                        <span>📍 {{ selectedRoom.location }}</span>
                        <span>
                            🎥
                            <template
                                v-for="(
                                    facility, index
                                ) in selectedRoom.facilities"
                                :key="facility.id"
                            >
                                {{ facility.name
                                }}<span
                                    v-if="
                                        index <
                                        selectedRoom.facilities.length - 1
                                    "
                                    >,
                                </span>
                            </template>
                        </span>
                    </div>

                    <button
                        class="booking ui-button ui-button--primary"
                        @click="booking(selectedRoom.id)"
                    >
                        Booking
                    </button>
                </div>
            </div>

            <div class="room-legend" v-else-if="filteredRooms.length">
                <span class="legend-title">Legenda Ruangan</span>

                <div class="legend-items">
                    <div
                        class="legend-item"
                        v-for="room in filteredRooms"
                        :key="room.id"
                    >
                        <span
                            class="legend-swatch"
                            :style="{
                                backgroundColor: getRoomColor(room.name),
                            }"
                        ></span>
                        <span>{{ room.name }}</span>
                    </div>
                </div>
            </div>

            <div class="calendar-card">
                <Calendar
                    :events="events"
                    :month="month"
                    :year="year"
                    :date="date"
                    :view="view"
                    @open-day="loadCalendar($event, 'day')"
                    @navigate="loadCalendar"
                    @change-view="changeView"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-card {
    display: grid;
    gap: var(--ui-gap);
}

.toolbar {
    min-width: 0;
    margin: 0;
    padding: 0;
    border: 0;
}

.toolbar legend {
    margin-bottom: 8px;
}

.room-filter-heading {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.room-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.room-option {
    border: 1px solid var(--ui-border);
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    padding: 8px 12px;
    border-radius: 8px;
    background: white;
    cursor: pointer;
}

.room-option input {
    width: 18px;
    height: 18px;
    accent-color: #2563eb;
}

.room-card {
    display: flex;
    gap: var(--ui-gap);
    padding: var(--ui-card-padding);
    background: var(--ui-surface-soft);
    border-radius: var(--ui-radius);
    border: 1px solid var(--ui-border);
}

.room-image {
    flex-shrink: 0;
    overflow: hidden;
    width: 120px;
    height: 120px;
    background: #f5f5f5;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 10px;
    font-size: 42px;
}

.room-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.room-info {
    flex: 1;
    min-width: 0;
    overflow-wrap: anywhere;
}

.room-info h3 {
    margin-bottom: 10px;
}

.meta {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    color: #666;
    margin-bottom: 20px;
}

.room-legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--ui-surface-soft);
    border-radius: var(--ui-radius);
    border: 1px solid var(--ui-border);
}

.legend-title {
    font-weight: 600;
    color: #173b7a;
    white-space: nowrap;
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 20px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #374151;
}

.legend-swatch {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    flex-shrink: 0;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.calendar-card {
    min-width: 0;
}

.calendar-frame {
    width: 100%;
    height: 100%;
    border: none;
}

@media (max-width: 768px) {
    .room-card {
        align-items: stretch;
        flex-direction: column;
    }
    .room-image {
        width: 100%;
        height: 160px;
    }
    .booking {
        width: 100%;
        min-height: 44px;
    }
}
</style>
