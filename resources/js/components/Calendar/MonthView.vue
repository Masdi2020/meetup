<script setup lang="ts">
import type { Dayjs } from 'dayjs';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { eventStatusLabels } from './eventStatusLabels';
import { getRoomColor, getRoomTextColor } from './roomColors';
import type { CalendarEvent } from '@/types/calendar';

const props = defineProps<{
    date: Dayjs;
    now: Dayjs;
    events: CalendarEvent[];
}>();

const emit = defineEmits<{
    select: [event: CalendarEvent];
    'open-day': [date: string];
}>();

const weekDays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
const grid = ref<HTMLElement | null>(null);
const gridHeight = ref(420);
let observer: ResizeObserver | undefined;
let frame = 0;
function resizeGrid() {
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(() => {
        if (!grid.value) {
            return;
        }

        const top = grid.value.getBoundingClientRect().top + window.scrollY;
        gridHeight.value = Math.max(
            (days.value.length / 7) * 44 + 30,
            window.innerHeight - top - 48,
        );
    });
}
onMounted(() => {
    observer = new ResizeObserver(resizeGrid);

    if (grid.value) {
        observer.observe(grid.value);
        const page = grid.value.closest('.availability');

        if (page) {
            observer.observe(page);
        }
    }

    window.addEventListener('resize', resizeGrid);
    resizeGrid();
});
onBeforeUnmount(() => {
    observer?.disconnect();
    window.removeEventListener('resize', resizeGrid);
    cancelAnimationFrame(frame);
});
watch(() => props.date, resizeGrid, { flush: 'post' });
const visibleCount = computed(() =>
    Math.max(
        0,
        Math.min(
            3,
            Math.floor(
                ((gridHeight.value - 30) / (days.value.length / 7) - 44) / 24,
            ),
        ),
    ),
);

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
    <div
        ref="grid"
        class="calendar-grid"
        :style="{
            height: `${gridHeight}px`,
            gridTemplateRows: `28px repeat(${days.length / 7}, minmax(0, 1fr))`,
        }"
    >
        <div class="header" v-for="day in weekDays" :key="day">
            {{ day }}
        </div>

        <div
            v-for="day in days"
            :key="day.toString()"
            class="cell"
            :class="{
                other: day.month() !== props.date.month(),
                today: day.isSame(now, 'day'),
            }"
            :aria-current="day.isSame(now, 'day') ? 'date' : undefined"
        >
            <div class="number">
                {{ day.date() }}
                <span v-if="day.isSame(now, 'day')" class="today-label"
                    >Hari ini</span
                >
            </div>

            <div v-if="getEventsForDate(day).length" class="event-list">
                <div
                    v-for="event in getEventsForDate(day).slice(
                        0,
                        visibleCount,
                    )"
                    :key="`${event.id}-${event.date}`"
                    class="event-pill"
                    :class="{ 'pending-event': event.status === 'PENDING' }"
                    role="button"
                    tabindex="0"
                    :aria-label="`${event.title}, ${event.start_time}-${event.end_time}, ${eventStatusLabels[event.status]}`"
                    :title="`${event.title}, ${event.start_time}-${event.end_time}, ${eventStatusLabels[event.status]}`"
                    :style="{
                        backgroundColor: getRoomColor(event.room),
                        color: getRoomTextColor(),
                    }"
                    @click="emit('select', event)"
                    @keydown.enter="emit('select', event)"
                    @keydown.space.prevent="emit('select', event)"
                >
                    <span class="event-title"
                        >{{ event.start_time }} {{ event.title }}</span
                    >
                    <span class="event-status">{{
                        eventStatusLabels[event.status]
                    }}</span>
                </div>
                <button
                    v-if="getEventsForDate(day).length > visibleCount"
                    type="button"
                    class="event-more"
                    :aria-label="`Lihat semua ${getEventsForDate(day).length} jadwal pada ${day.format('DD/MM/YYYY')}`"
                    @click="emit('open-day', day.format('YYYY-MM-DD'))"
                >
                    {{ visibleCount ? '+' : ''
                    }}{{ getEventsForDate(day).length - visibleCount }}
                    <span>jadwal</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 1px;
    padding: 1px;
    border: 1px solid #94a3b8;
    background: #94a3b8;
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

.header {
    background: #2563eb;
    color: white;
    padding: 4px;
    font-weight: bold;
    text-align: center;
    box-sizing: border-box;
}

.cell {
    background: white;
    min-height: 0;
    min-width: 0;
    padding: 3px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-sizing: border-box;
}

.number {
    display: flex;
    white-space: nowrap;
    align-items: center;
    gap: 4px;
    font-weight: bold;
}

.other {
    color: #bbb;
    background: #fafafa;
}

.today {
    box-shadow: inset 0 0 0 2px #2563eb;
    background: #eff6ff;
}

.event-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin-top: 2px;
    flex: 1;
    min-height: 0;
}

.event-pill {
    box-sizing: border-box;
    flex-direction: row;
    width: 100%;
    border-radius: 6px;
    padding: 2px 4px;
    font-size: 11px;
    line-height: 1.3;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 3px;
    height: 22px;
    flex-shrink: 0;
    overflow: hidden;
    cursor: pointer;
    text-align: left;
}

.event-pill:hover,
.event-pill:focus-visible {
    filter: brightness(0.92);
}

.event-title {
    max-width: 100%;
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
    border: 0;
    padding: 0;
    min-height: 20px;
    background: transparent;
    cursor: pointer;
    text-align: left;
    font-size: 11px;
    color: #1d4ed8;
}
.event-status {
    font-size: 9px;
    flex-shrink: 0;
}
.event-more:focus-visible {
    outline: 2px solid #2563eb;
}

@media (max-width: 768px) {
    .header {
        padding: 4px 0;
        font-size: 11px;
    }
    .cell {
        padding: 2px;
    }
    .event-pill {
        width: 100%;
        padding: 1px;
        font-size: 9px;
        gap: 1px;
    }
    .event-status,
    .number .today-label {
        display: none;
    }
    .today .number {
        color: #1d4ed8;
    }
    .number {
        font-size: 12px;
    }
    .event-more {
        font-size: 10px;
    }
}
</style>
