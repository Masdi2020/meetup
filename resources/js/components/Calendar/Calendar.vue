<script setup lang="ts">
import dayjs from "dayjs";
import { ref, computed } from "vue";

import CalendarToolbar from "./CalendarToolbar.vue";
import MonthView from "./MonthView.vue";

const currentDate = ref(dayjs());

const title = computed(() =>
    currentDate.value.format("MMMM YYYY")
);

function previousMonth() {
    currentDate.value = currentDate.value.subtract(1, "month");
}

function nextMonth() {
    currentDate.value = currentDate.value.add(1, "month");
}

function today() {
    currentDate.value = dayjs();
}
</script>

<template>

    <div class="calendar">

        <CalendarToolbar
            :title="title"
            @previous="previousMonth"
            @next="nextMonth"
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
