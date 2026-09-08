<script setup lang="ts">
import type { Dayjs } from 'dayjs';
import { computed } from 'vue';
import EventStatus from './EventStatus.vue';
import { getRoomColor, getRoomTextColor } from './roomColors';
import type { CalendarEvent } from '@/types/calendar';

const props = defineProps<{
    date: Dayjs;
    now: Dayjs;
    events: CalendarEvent[];
}>();
const emit = defineEmits<{ select: [event: CalendarEvent] }>();
const dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
const startOfWeek = computed(() => {
    const weekday = props.date.day();

    return props.date.subtract(weekday === 0 ? 6 : weekday - 1, 'day');
});
const days = computed(() =>
    Array.from({ length: 7 }, (_, index) =>
        startOfWeek.value.add(index, 'day'),
    ),
);
function getEventsForDate(date: Dayjs) {
    return props.events.filter(
        (event) => event.date === date.format('YYYY-MM-DD'),
    );
}
</script>

<template>
    <div class="week-grid">
        <section
            v-for="(day, index) in days"
            :key="day.format('YYYY-MM-DD')"
            class="day-column"
            :class="{ today: day.isSame(now, 'day') }"
            :aria-current="day.isSame(now, 'day') ? 'date' : undefined"
        >
            <header class="day-header">
                <span>{{ dayNames[index] }}</span
                ><strong>{{ day.date() }}</strong
                ><small>{{ day.format('MMM') }}</small>
                <span v-if="day.isSame(now, 'day')" class="today-label"
                    >Hari ini</span
                >
            </header>
            <div class="day-events">
                <button
                    v-for="event in getEventsForDate(day)"
                    :key="`${event.id}-${event.date}`"
                    type="button"
                    class="week-event"
                    :class="{ 'pending-event': event.status === 'PENDING' }"
                    :style="{
                        backgroundColor: getRoomColor(event.room),
                        color: getRoomTextColor(),
                    }"
                    :aria-label="`Lihat rincian ${event.title}`"
                    @click="emit('select', event)"
                >
                    <span class="event-time"
                        >{{ event.start_time }}-{{ event.end_time }}</span
                    >
                    <strong>{{ event.title }}</strong>
                    <EventStatus :status="event.status" />
                    <span class="event-room">{{ event.room }}</span>
                </button>
                <p v-if="!getEventsForDate(day).length" class="empty-day">
                    Tidak ada jadwal
                </p>
            </div>
        </section>
    </div>
</template>

<style scoped>
.week-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(140px, 1fr));
    width: max-content;
    min-width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    overflow: hidden;
    background: #e2e8f0;
    gap: 1px;
}
.day-column {
    min-height: 420px;
    background: #fff;
}
.day-header {
    flex-wrap: wrap;
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 5px;
    padding: 13px 8px;
    border-bottom: 1px solid #e2e8f0;
    color: #475569;
}
.day-header strong {
    display: grid;
    place-items: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    color: #0f172a;
    font-size: 18px;
}
.day-header small {
    text-transform: uppercase;
}
.today .day-header {
    color: #1d4ed8;
    background: #eff6ff;
}
.today .day-header strong {
    background: #2563eb;
    color: #fff;
}
.day-events {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px;
}
.week-event {
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 3px;
    width: 100%;
    padding: 9px;
    border: 0;
    border-radius: 8px;
    cursor: pointer;
    text-align: left;
}
.week-event:hover,
.week-event:focus-visible {
    filter: brightness(0.92);
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}
.event-time {
    font-size: 11px;
    font-weight: 700;
}
.week-event strong {
    overflow-wrap: anywhere;
    font-size: 13px;
}
.event-room {
    font-size: 11px;
    opacity: 0.8;
}
.empty-day {
    margin: 24px 0;
    color: #94a3b8;
    font-size: 12px;
    text-align: center;
}
@media (max-width: 768px) {
    .week-grid {
        grid-template-columns: repeat(7, minmax(118px, 1fr));
    }
    .day-column {
        min-height: 320px;
    }
}
</style>
