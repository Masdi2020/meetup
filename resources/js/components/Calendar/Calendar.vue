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
</script>

<template>
    <div class="calendar">
        <CalendarToolbar
            :title="title"
            @previous="emit('previous')"
            @next="emit('next')"
            @today="today"
        />

        <MonthView :date="currentDate" :events="props.events" />
    </div>
</template>

<style scoped>
.calendar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
</style>
