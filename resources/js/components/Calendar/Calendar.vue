<script setup lang="ts">
import dayjs from 'dayjs';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

import CalendarToolbar from './CalendarToolbar.vue';
import DayView from './DayView.vue';
import EventStatus from './EventStatus.vue';
import MonthView from './MonthView.vue';
import WeekView from './WeekView.vue';
import type { CalendarEvent } from '@/types/calendar';

const props = defineProps<{
    events: CalendarEvent[];
    month: number;
    year: number;
    date: string;
    view: 'month' | 'week' | 'day';
}>();

const emit = defineEmits<{
    navigate: [date: string];
    'change-view': [view: 'month' | 'week' | 'day'];
}>();

const currentDate = ref(dayjs());
const now = ref(dayjs());
let clockTimer: ReturnType<typeof setInterval> | undefined;
function updateClock() {
    now.value = dayjs();
}
onMounted(() => {
    clockTimer = setInterval(updateClock, 30_000);
    document.addEventListener('visibilitychange', updateClock);
});
onBeforeUnmount(() => {
    clearInterval(clockTimer);
    document.removeEventListener('visibilitychange', updateClock);
});
const selectedEvent = ref<CalendarEvent | null>(null);

watch(
    () => props.events,
    (events) => {
        if (selectedEvent.value) {
            selectedEvent.value =
                events.find((event) => event.id === selectedEvent.value?.id) ??
                null;
        }
    },
);

const startOfWeek = computed(() => {
    const weekday = currentDate.value.day();

    return currentDate.value.subtract(weekday === 0 ? 6 : weekday - 1, 'day');
});
const endOfWeek = computed(() => startOfWeek.value.add(6, 'day'));
const title = computed(() => {
    if (props.view === 'month') {
        return currentDate.value.format('MMMM YYYY');
    }

    if (props.view === 'day') {
        return currentDate.value.format('dddd, D MMMM YYYY');
    }

    if (startOfWeek.value.month() === endOfWeek.value.month()) {
        return `${startOfWeek.value.date()}-${endOfWeek.value.date()} ${endOfWeek.value.format('MMMM YYYY')}`;
    }

    return `${startOfWeek.value.format('D MMM')}-${endOfWeek.value.format('D MMM YYYY')}`;
});

watch(
    () => props.date,
    (date) => {
        currentDate.value = dayjs(date);
    },
    { immediate: true },
);

function today() {
    currentDate.value = dayjs();
    emit('navigate', currentDate.value.format('YYYY-MM-DD'));
}

function navigate(direction: -1 | 1) {
    const unit =
        props.view === 'day' ? 'day' : props.view === 'week' ? 'week' : 'month';
    emit(
        'navigate',
        currentDate.value.add(direction, unit).format('YYYY-MM-DD'),
    );
}

async function closeDetails() {
    selectedEvent.value = null;
    await nextTick();

    if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
    }
}

function closeDetailsOnEscape(event: KeyboardEvent) {
    if (event.key === 'Escape' && selectedEvent.value) {
        closeDetails();
    }
}

onMounted(() => window.addEventListener('keydown', closeDetailsOnEscape));
onBeforeUnmount(() =>
    window.removeEventListener('keydown', closeDetailsOnEscape),
);

const formattedSelectedDate = computed(() => {
    if (!selectedEvent.value) {
        return '';
    }

    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${selectedEvent.value.date}T00:00:00Z`));
});
</script>

<template>
    <div class="calendar">
        <CalendarToolbar
            :title="title"
            :view="props.view"
            @previous="navigate(-1)"
            @next="navigate(1)"
            @today="today"
            @update:view="emit('change-view', $event)"
        />

        <div class="calendar-legend" aria-label="Keterangan kalender">
            <EventStatus status="PENDING" />
            <span>Garis putus-putus: menunggu persetujuan</span>
            <EventStatus status="APPROVED" />
            <EventStatus status="FINISHED" />
            <span class="today-label">Hari ini</span>
        </div>
        <div class="calendar-scroll">
            <MonthView
                v-if="props.view === 'month'"
                :date="currentDate"
                :now="now"
                :events="props.events"
                @select="selectedEvent = $event"
            />
            <WeekView
                v-else-if="props.view === 'week'"
                :date="currentDate"
                :now="now"
                :events="props.events"
                @select="selectedEvent = $event"
            />
            <DayView
                v-else
                :date="currentDate"
                :now="now"
                :events="props.events"
                @select="selectedEvent = $event"
            />
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="selectedEvent"
            class="detail-overlay"
            @click.self="closeDetails"
        >
            <section
                class="detail-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="booking-detail-title"
            >
                <div class="detail-header">
                    <div>
                        <span class="detail-eyebrow">Rincian Peminjaman</span>
                        <h3 id="booking-detail-title">
                            {{ selectedEvent.title }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="detail-close"
                        aria-label="Tutup rincian"
                        @click="closeDetails"
                    >
                        &times;
                    </button>
                </div>
                <dl class="detail-list">
                    <div>
                        <dt>Status</dt>
                        <dd><EventStatus :status="selectedEvent.status" /></dd>
                    </div>
                    <div>
                        <dt>Peminjam</dt>
                        <dd>{{ selectedEvent.borrower }}</dd>
                    </div>
                    <div>
                        <dt>Ruangan</dt>
                        <dd>{{ selectedEvent.room }}</dd>
                    </div>
                    <div>
                        <dt>Tanggal</dt>
                        <dd>{{ formattedSelectedDate }}</dd>
                    </div>
                    <div>
                        <dt>Waktu</dt>
                        <dd>
                            {{ selectedEvent.start_time }} -
                            {{ selectedEvent.end_time }}
                        </dd>
                    </div>
                    <div>
                        <dt>Jumlah peserta</dt>
                        <dd>{{ selectedEvent.participants_count }} orang</dd>
                    </div>
                    <div class="detail-notes">
                        <dt>Catatan</dt>
                        <dd>
                            {{ selectedEvent.notes || 'Tidak ada catatan.' }}
                        </dd>
                    </div>
                </dl>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.calendar-legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    color: #475569;
    font-size: 12px;
}
.calendar :deep(.pending-event) {
    border: 2px dashed #92400e;
}
.calendar :deep(.today-label) {
    display: inline-block;
    border-radius: 4px;
    padding: 2px 5px;
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 10px;
    font-weight: 700;
}
.calendar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.calendar-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.detail-overlay {
    position: fixed;
    z-index: 1000;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgba(15, 23, 42, 0.58);
}
.detail-modal {
    width: min(520px, 100%);
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    border-radius: 16px;
    background: #fff;
    padding: 24px;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
}
.detail-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e2e8f0;
}
.detail-eyebrow {
    color: #2563eb;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.detail-header h3 {
    margin: 5px 0 0;
    color: #173b7a;
    font-size: 21px;
}
.detail-close {
    flex: 0 0 auto;
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 50%;
    background: #f1f5f9;
    color: #334155;
    cursor: pointer;
    font-size: 25px;
    line-height: 1;
}
.detail-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px 24px;
    margin: 22px 0 0;
}
.detail-list div {
    min-width: 0;
}
.detail-list dt {
    margin-bottom: 4px;
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}
.detail-list dd {
    margin: 0;
    color: #1e293b;
    overflow-wrap: anywhere;
}
.detail-notes {
    grid-column: 1 / -1;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
    white-space: pre-wrap;
}
@media (max-width: 520px) {
    .detail-modal {
        padding: 20px;
    }
    .detail-list {
        grid-template-columns: 1fr;
    }
    .detail-notes {
        grid-column: auto;
    }
}
</style>
