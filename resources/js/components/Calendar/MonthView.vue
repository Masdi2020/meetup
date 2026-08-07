<script setup lang="ts">
import dayjs from "dayjs";
import type { Dayjs } from "dayjs";
import { computed } from "vue";

interface CalendarEvent {
    id: number;
    title: string;
    room: string;
    date: string;
    start_time: string;
    end_time: string;
}

const props = defineProps<{
    date: Dayjs;
    events: CalendarEvent[];
}>();

const weekDays = [
    "Sen",
    "Sel",
    "Rab",
    "Kam",
    "Jum",
    "Sab",
    "Min",
];

const days = computed(() => {
    const firstDay = props.date.startOf("month");
    const start = firstDay.startOf("week").add(1, "day");

    return Array.from({ length: 42 }, (_, index) => start.add(index, "day"));
});

function getEventsForDate(day: Dayjs) {
    return props.events.filter((event) => event.date === day.format("YYYY-MM-DD"));
}

function getEventColor(event: CalendarEvent) {
    const base = event.room.split("").reduce((acc, char) => acc + char.charCodeAt(0), 0);
    const hue = base % 360;

    return `hsl(${hue} 70% 92%)`;
}

function getEventTextColor(event: CalendarEvent) {
    const base = event.room.split("").reduce((acc, char) => acc + char.charCodeAt(0), 0);
    const hue = base % 360;

    return `hsl(${hue} 55% 28%)`;
}
</script>

<template>
    <div class="calendar-grid">
        <div class="header" v-for="day in weekDays" :key="day">
            {{ day }}
        </div>

        <div
            v-for="day in days"
            :key="day.toString()"
            class="cell"
            :class="{
                other: day.month() !== props.date.month(),
                today: day.isSame(dayjs(), 'day'),
            }"
        >
            <div class="number">
                {{ day.date() }}
            </div>

            <div v-if="getEventsForDate(day).length" class="event-list">
                <div
                    v-for="event in getEventsForDate(day).slice(0, 3)"
                    :key="`${event.id}-${event.date}`"
                    class="event-pill"
                    :style="{
                        backgroundColor: getEventColor(event),
                        color: getEventTextColor(event),
                    }"
                >
                    <span class="event-title">{{ event.title }}</span>
                    <span class="event-time">{{ event.start_time }}-{{ event.end_time }}</span>
                </div>

                <div
                    v-if="getEventsForDate(day).length > 3"
                    class="event-more"
                >
                    +{{ getEventsForDate(day).length - 3 }} lagi
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #ddd;
}

.header {
    background: #2563eb;
    color: white;
    padding: 12px;
    font-weight: bold;
    text-align: center;
}

.cell {
    background: white;
    min-height: 132px;
    padding: 8px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.number {
    font-weight: bold;
}

.other {
    color: #bbb;
    background: #fafafa;
}

.today {
    outline: 3px solid #2563eb;
}

.event-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: 6px;
    flex: 1;
    overflow-y: auto;
}

.event-pill {
    border-radius: 6px;
    padding: 4px 6px;
    font-size: 11px;
    line-height: 1.3;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 6px;
    min-height: 30px;
}

.event-title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
}

.event-time {
    white-space: nowrap;
    font-weight: 600;
}

.event-more {
    font-size: 11px;
    color: #64748b;
}
</style>
