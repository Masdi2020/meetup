<script setup lang="ts">
import dayjs from 'dayjs';
import { ref, computed, watch } from 'vue';

import CalendarToolbar from './CalendarToolbar.vue';
import MonthView from './MonthView.vue';
import type { CalendarEvent } from '@/types/calendar';

const props = defineProps<{
    events: CalendarEvent[];
    month: number;
    year: number;
}>();

const emit = defineEmits(['previous', 'next', 'today']);

const currentDate = ref(dayjs());
const selectedEvent = ref<CalendarEvent | null>(null);

const title = computed(() => currentDate.value.format('MMMM YYYY'));

watch(
    () => [props.month, props.year],
    ([month, year]) => {
        currentDate.value = dayjs(`${year}-${month}-01`);
    },
    { immediate: true },
);

function today() {
    currentDate.value = dayjs();
    emit('today');
}

const formattedSelectedDate = computed(() => {
    if (!selectedEvent.value) return '';

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
            @previous="emit('previous')"
            @next="emit('next')"
            @today="today"
        />

        <div class="calendar-scroll">
            <MonthView
                :date="currentDate"
                :events="props.events"
                @select="selectedEvent = $event"
            />
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="selectedEvent"
            class="detail-overlay"
            @click.self="selectedEvent = null"
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
                        @click="selectedEvent = null"
                    >
                        &times;
                    </button>
                </div>
                <dl class="detail-list">
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
                            {{ selectedEvent.start_time }} - {{
                                selectedEvent.end_time
                            }}
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
