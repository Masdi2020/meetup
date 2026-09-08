<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Calendar from '@/components/Calendar/Calendar.vue';
import { getRoomColor } from '@/components/Calendar/roomColors';
import RoomSelector from '@/components/molecules/RoomSelector.vue';
import RoomDetailModal from '@/components/organisms/RoomDetailModal.vue';
import { useBookingPageUpdates } from '@/composables/useBookingUpdates';
import type { CalendarEvent } from '@/types/calendar';
import type { AvailabilityRoom, RoomDetails } from '@/types/room';

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
const selectedDetailRoom = ref<RoomDetails | null>(null);
const filteredRooms = computed(() =>
    rooms.filter((room) => selectedRoomIds.value.includes(room.id)),
);
const roomFilter = computed(() =>
    selectedRoomIds.value.length ? selectedRoomIds.value : [0],
);

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
            <RoomSelector
                v-model="selectedRoomIds"
                :rooms="rooms"
                @detail="selectedDetailRoom = $event"
            />

            <div class="room-legend" v-if="filteredRooms.length">
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
    <RoomDetailModal
        v-if="selectedDetailRoom"
        :room="selectedDetailRoom"
        @close="selectedDetailRoom = null"
    />
</template>

<style scoped>
.page-card {
    display: grid;
    gap: var(--ui-gap);
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

</style>
