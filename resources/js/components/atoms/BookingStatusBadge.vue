<script setup lang="ts">
import { computed } from 'vue';
import { eventStatusLabels } from '@/lib/eventStatusLabels';
import type {
    BookingStatus,
    BookingStatusCode,
    BookingHistoryStatus,
} from '@/types/booking';

const props = withDefaults(
    defineProps<{
        status: BookingStatus | BookingStatusCode | BookingHistoryStatus;
        appearance?: 'default' | 'calendar';
    }>(),
    { appearance: 'default' },
);
const code = computed(() => props.status.toUpperCase() as BookingStatusCode);
</script>

<template>
    <span
        class="booking-status"
        :class="[
            `booking-status--${status.toLowerCase()}`,
            `booking-status--${appearance}`,
        ]"
    >
        {{ appearance === 'calendar' ? eventStatusLabels[code] : status }}
    </span>
</template>

<style scoped>
.booking-status {
    display: inline-block;
    width: fit-content;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    text-transform: capitalize;
}
.booking-status--pending {
    background: #fef3c7;
    color: #92400e;
}
.booking-status--approved {
    background: #dcfce7;
    color: #15803d;
}
.booking-status--rejected {
    background: #fee2e2;
    color: #b91c1c;
}
.booking-status--cancelled {
    background: #f1f5f9;
    color: #475569;
}
.booking-status--finished {
    background: #dbeafe;
    color: #1d4ed8;
}
.booking-status--calendar {
    padding: 2px 5px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    line-height: 1.2;
}
.booking-status--calendar.booking-status--finished {
    background: #e2e8f0;
    color: #334155;
}
</style>
