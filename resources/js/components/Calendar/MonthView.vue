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
                    v-for="event in getEventsForDate(day).slice(0, 2)"
                    :key="`${event.id}-${event.date}`"
                    class="event-pill"
                >
                    {{ event.title }}
                </div>

                <div
                    v-if="getEventsForDate(day).length > 2"
                    class="event-more"
                >
                    +{{ getEventsForDate(day).length - 2 }} lagi
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
    min-height: 120px;
    padding: 8px;
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
}

.event-pill {
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 6px;
    padding: 4px 6px;
    font-size: 11px;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.event-more {
    font-size: 11px;
    color: #64748b;
}
</style>
