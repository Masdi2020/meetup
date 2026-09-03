<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Calendar from '@/components/Calendar/Calendar.vue';
import { getRoomColor } from '@/components/Calendar/roomColors';
import type { CalendarEvent } from '@/types/calendar';
import type { AvailabilityRoom } from '@/types/room';

const props = defineProps<{
    rooms: AvailabilityRoom[];
    events: CalendarEvent[];
    month: number;
    year: number;
    date: string;
    view: 'month' | 'week' | 'day';
    selectedRoomId: number;
}>();

const rooms = props.rooms;

const selectedRoomId = ref<number | string>(props.selectedRoomId ?? 0);

const selectedRoom = computed(() => {
    if (selectedRoomId.value === 0 || selectedRoomId.value === '0') {
        return null;
    }

    return rooms.find((room) => room.id === Number(selectedRoomId.value));
});

function booking(roomId: number) {
    localStorage.setItem('booking_room_id', String(roomId));

    router.get('/booking');
}

function loadCalendar(date: string, view = props.view) {
    router.get(
        '/availability',
        { room: selectedRoomId.value, date, view },
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
watch(selectedRoomId, (room) => {
    router.get(
        '/availability',
        {
            room,
            date: props.date,
            view: props.view,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['events', 'selectedRoomId'],
        },
    );
});
</script>

<template>
    <div class="availability">
        <h2>Ketersediaan Ruangan</h2>

        <div class="page-card">
            <div class="toolbar">
                <label>Pilih Ruangan</label>

                <select v-model="selectedRoomId">
                    <option :value="0">Semua Ruangan</option>
                    <option
                        v-for="room in rooms"
                        :key="room.id"
                        :value="room.id"
                    >
                        {{ room.name }}
                    </option>
                </select>
            </div>

            <div class="room-card" v-if="selectedRoom && selectedRoomId !== 0">
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

                    <button class="booking" @click="booking(selectedRoom.id)">
                        Booking
                    </button>
                </div>
            </div>

            <div class="room-legend" v-else-if="rooms.length">
                <span class="legend-title">Legenda Ruangan</span>

                <div class="legend-items">
                    <div
                        class="legend-item"
                        v-for="room in rooms"
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
                    @navigate="loadCalendar"
                    @change-view="changeView"
                />
            </div>
        </div>
    </div>
</template>

<style>
.availability {
    width: 100%;
    margin: 0;
    padding: 30px;
}

.page-card {
    background: #cfe2ff;
    border-radius: 10px;
    padding: 28px;
    max-width: 1100px;
    margin: 0; /* align left with heading */
}

h2 {
    font-size: 24px;
    margin-bottom: 15px;
    color: #173b7a;
    border-bottom: 2px solid #d9d9d9;
    width: fit-content;
}

.toolbar {
    margin: 20px 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.toolbar select {
    width: 300px;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.room-card {
    display: flex;
    gap: 20px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    margin-bottom: 20px;
}

.room-image {
    width: 120px;
    height: 120px;
    background: #f5f5f5;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 10px;
    font-size: 42px;
}

.room-info {
    flex: 1;
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

.booking {
    padding: 10px 20px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.room-legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    margin-bottom: 20px;
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
    border: 1px solid #ddd;
    border-radius: 12px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding: 8px;
    background: white;
}

.calendar-frame {
    width: 100%;
    height: 100%;
    border: none;
}

@media (max-width: 768px) {
    .toolbar select {
        width: 100%;
        min-height: 44px;
    }
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
    .room-legend {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
