<script setup lang="ts">
import dayjs from "dayjs";
import { ref, computed } from "vue";

import CalendarToolbar from "./CalendarToolbar.vue";
import MonthView from "./MonthView.vue";

const emit = defineEmits([
    "previous",
    "next"
]);

const currentDate = ref(dayjs());

const title = computed(() =>
    currentDate.value.format("MMMM YYYY")
);

function today() {
    currentDate.value = dayjs();
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

        <MonthView
            :date="currentDate"
        />

    </div>

</template>

<style scoped>

.calendar{

    display:flex;
    flex-direction:column;
    gap:20px;

}

</style>
