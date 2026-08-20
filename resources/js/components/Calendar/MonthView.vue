<script setup lang="ts">
import dayjs from 'dayjs';
import type { Dayjs } from 'dayjs';
import { computed } from 'vue';
import { getRoomColor, getRoomTextColor } from './roomColors';

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

const weekDays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const days = computed(() => {
    const firstDay = props.date.startOf('month');
    const lastDay = props.date.endOf('month');

    const firstWeekday = firstDay.day();
    const daysToMonday = firstWeekday === 0 ? 6 : firstWeekday - 1;
    const start = firstDay.subtract(daysToMonday, 'day');

    const lastWeekday = lastDay.day();
    const daysToSunday = lastWeekday === 0 ? 0 : 7 - lastWeekday;
    const end = lastDay.add(daysToSunday, 'day');

    const totalDays = end.diff(start, 'day') + 1;

    return Array.from({ length: totalDays }, (_, index) =>
        start.add(index, 'day'),
    );
});

function getEventsForDate(day: Dayjs) {
    return props.events.filter(
        (event) => event.date === day.format('YYYY-MM-DD'),
    );
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
                        backgroundColor: getRoomColor(event.room),
                        color: getRoomTextColor(),
                    }"
                >
                    <span class="event-title">{{ event.title }}</span>
                    <span class="event-time"
                        >{{ event.start_time }}-{{ event.end_time }}</span
                    >
                </div>

                <div v-if="getEventsForDate(day).length > 3" class="event-more">
                    +{{ getEventsForDate(day).length - 3 }} lagi
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(120px, 1fr));
    grid-auto-rows: minmax(140px, auto);
    gap: 1px;
    background: #ddd;
    width: 100%;
}

.header {
    background: #2563eb;
    color: white;
    padding: 12px;
    font-weight: bold;
    text-align: center;
    box-sizing: border-box;
}

.cell {
    background: white;
    min-height: 140px;
    padding: 8px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-sizing: border-box;
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
