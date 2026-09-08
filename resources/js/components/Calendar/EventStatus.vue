<script setup lang="ts">
import { eventStatusLabels } from './eventStatusLabels';
import type { CalendarEvent } from '@/types/calendar';

defineProps<{ status: CalendarEvent['status'] }>();
</script>

<template>
    <span
        class="event-status"
        :class="{
            pending: status === 'PENDING',
            finished: status === 'FINISHED',
        }"
    >
        {{ eventStatusLabels[status] }}
    </span>
</template>

<style scoped>
.event-status {
    display: inline-block;
    width: fit-content;
    padding: 2px 5px;
    border-radius: 4px;
    background: #dcfce7;
    color: #166534;
    font-size: 10px;
    font-weight: 700;
    line-height: 1.2;
}
.pending {
    background: #fef3c7;
    color: #92400e;
}
.finished {
    background: #e2e8f0;
    color: #334155;
}
</style>
