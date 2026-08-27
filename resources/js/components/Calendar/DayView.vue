<script setup lang="ts">
import dayjs from 'dayjs';
import type { Dayjs } from 'dayjs';
import { computed } from 'vue';
import { getRoomColor, getRoomTextColor } from './roomColors';
import type { CalendarEvent } from '@/types/calendar';

interface PositionedEvent {
    event: CalendarEvent;
    start: number;
    end: number;
    column: number;
    columns: number;
}

const props = defineProps<{ date: Dayjs; events: CalendarEvent[] }>();
const emit = defineEmits<{ select: [event: CalendarEvent] }>();

const hourHeight = 64;
const hours = Array.from({ length: 24 }, (_, hour) => hour);

function timeToMinutes(time: string) {
    const [hour = 0, minute = 0] = time.split(':').map(Number);

    return Math.min(1440, Math.max(0, hour * 60 + minute));
}

const eventsForDay = computed(() =>
    props.events
        .filter((event) => event.date === props.date.format('YYYY-MM-DD'))
        .sort((first, second) =>
            first.start_time.localeCompare(second.start_time),
        ),
);

const positionedEvents = computed<PositionedEvent[]>(() => {
    const entries = eventsForDay.value.map((event) => ({
        event,
        start: timeToMinutes(event.start_time),
        end: Math.max(
            timeToMinutes(event.start_time) + 1,
            timeToMinutes(event.end_time),
        ),
        column: 0,
        columns: 1,
    }));
    const groups: PositionedEvent[][] = [];
    let group: PositionedEvent[] = [];
    let groupEnd = -1;

    for (const entry of entries) {
        if (group.length && entry.start >= groupEnd) {
            groups.push(group);
            group = [];
            groupEnd = -1;
        }

        group.push(entry);
        groupEnd = Math.max(groupEnd, entry.end);
    }

    if (group.length) {
        groups.push(group);
    }

    for (const overlappingGroup of groups) {
        const columnEnds: number[] = [];

        for (const entry of overlappingGroup) {
            let column = columnEnds.findIndex((end) => end <= entry.start);

            if (column === -1) {
                column = columnEnds.length;
                columnEnds.push(entry.end);
            } else {
                columnEnds[column] = entry.end;
            }

            entry.column = column;
        }

        for (const entry of overlappingGroup) {
            entry.columns = columnEnds.length;
        }
    }

    return entries;
});

const currentTimePosition = computed(() => {
    if (!props.date.isSame(dayjs(), 'day')) {
        return null;
    }

    const now = dayjs();

    return ((now.hour() * 60 + now.minute()) / 60) * hourHeight;
});

function eventStyle(entry: PositionedEvent) {
    const columnWidth = 100 / entry.columns;

    return {
        top: `${(entry.start / 60) * hourHeight}px`,
        height: `${Math.max(18, ((entry.end - entry.start) / 60) * hourHeight)}px`,
        left: `calc(${entry.column * columnWidth}% + 5px)`,
        width: `calc(${columnWidth}% - 10px)`,
        backgroundColor: getRoomColor(entry.event.room),
        color: getRoomTextColor(),
    };
}
</script>

<template>
    <section class="day-view">
        <header class="day-summary">
            <div class="date-badge">
                <span>{{ date.format('ddd') }}</span>
                <strong>{{ date.date() }}</strong>
            </div>
            <div>
                <p>Agenda harian</p>
                <strong>{{ eventsForDay.length }} jadwal</strong>
            </div>
        </header>

        <div class="timeline-scroll">
            <div class="timeline" :style="{ height: `${24 * hourHeight}px` }">
                <div class="hour-labels" aria-hidden="true">
                    <div
                        v-for="hour in hours"
                        :key="hour"
                        class="hour-label"
                        :style="{ height: `${hourHeight}px` }"
                    >
                        {{ String(hour).padStart(2, '0') }}:00
                    </div>
                </div>

                <div class="time-grid">
                    <div
                        v-for="hour in hours"
                        :key="hour"
                        class="hour-row"
                        :style="{ height: `${hourHeight}px` }"
                    ></div>

                    <button
                        v-for="entry in positionedEvents"
                        :key="`${entry.event.id}-${entry.event.date}`"
                        type="button"
                        class="timeline-event"
                        :class="{ compact: entry.end - entry.start < 45 }"
                        :style="eventStyle(entry)"
                        :aria-label="`Lihat rincian ${entry.event.title}, ${entry.event.start_time} sampai ${entry.event.end_time}`"
                        @click="emit('select', entry.event)"
                    >
                        <strong>{{ entry.event.title }}</strong>
                        <span>
                            {{ entry.event.start_time }}-{{
                                entry.event.end_time
                            }}
                            <template v-if="entry.end - entry.start >= 45">
                                · {{ entry.event.room }}
                            </template>
                        </span>
                    </button>

                    <div
                        v-if="currentTimePosition !== null"
                        class="current-time"
                        :style="{ top: `${currentTimePosition}px` }"
                        aria-label="Waktu sekarang"
                    >
                        <span></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.day-view {
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
}
.day-summary {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 22px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}
.date-badge {
    display: grid;
    place-items: center;
    width: 54px;
    height: 58px;
    border-radius: 10px;
    background: #2563eb;
    color: #fff;
    text-transform: uppercase;
}
.date-badge span {
    font-size: 11px;
    font-weight: 700;
}
.date-badge strong {
    font-size: 24px;
    line-height: 1;
}
.day-summary p {
    margin: 0 0 3px;
    color: #64748b;
    font-size: 13px;
}
.day-summary > div:last-child > strong {
    color: #173b7a;
    font-size: 18px;
}
.timeline-scroll {
    max-height: min(68vh, 760px);
    overflow-y: auto;
    overscroll-behavior: contain;
}
.timeline {
    display: grid;
    grid-template-columns: 70px minmax(520px, 1fr);
    min-width: 620px;
}
.hour-labels {
    background: #fff;
}
.hour-label {
    box-sizing: border-box;
    padding: 0 10px 0 0;
    color: #64748b;
    font-size: 11px;
    text-align: right;
    transform: translateY(-7px);
}
.hour-label:first-child {
    padding-top: 8px;
    transform: none;
}
.time-grid {
    position: relative;
    border-left: 1px solid #e2e8f0;
}
.hour-row {
    box-sizing: border-box;
    border-top: 1px solid #e2e8f0;
    background-image: linear-gradient(
        to bottom,
        transparent calc(50% - 0.5px),
        #f1f5f9 calc(50% - 0.5px),
        #f1f5f9 calc(50% + 0.5px),
        transparent calc(50% + 0.5px)
    );
}
.timeline-event {
    position: absolute;
    z-index: 2;
    display: flex;
    align-items: flex-start;
    flex-direction: column;
    box-sizing: border-box;
    min-width: 0;
    padding: 5px 8px;
    overflow: hidden;
    border: 0;
    border-left: 4px solid rgba(15, 23, 42, 0.24);
    border-radius: 6px;
    cursor: pointer;
    line-height: 1.2;
    text-align: left;
}
.timeline-event:hover,
.timeline-event:focus-visible {
    z-index: 4;
    filter: brightness(0.92);
    outline: 2px solid #2563eb;
    outline-offset: 1px;
}
.timeline-event strong,
.timeline-event span {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.timeline-event strong {
    font-size: 12px;
}
.timeline-event span {
    margin-top: 2px;
    font-size: 10px;
}
.timeline-event.compact {
    justify-content: center;
    padding-top: 1px;
    padding-bottom: 1px;
}
.timeline-event.compact strong {
    font-size: 10px;
}
.timeline-event.compact span {
    display: none;
}
.current-time {
    position: absolute;
    z-index: 3;
    right: 0;
    left: 0;
    height: 2px;
    background: #ef4444;
    pointer-events: none;
}
.current-time span {
    position: absolute;
    top: -4px;
    left: -5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ef4444;
}
@media (max-width: 640px) {
    .day-summary {
        padding-right: 14px;
        padding-left: 14px;
    }
    .timeline {
        grid-template-columns: 56px minmax(480px, 1fr);
        min-width: 536px;
    }
    .hour-label {
        padding-right: 7px;
    }
}
</style>
