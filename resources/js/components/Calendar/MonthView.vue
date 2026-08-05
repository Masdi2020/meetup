<script setup lang="ts">
import dayjs from "dayjs";
import type { Dayjs } from "dayjs";
import { computed } from "vue";

const props = defineProps<{

    date:Dayjs

}>();

const weekDays=[
    "Sen",
    "Sel",
    "Rab",
    "Kam",
    "Jum",
    "Sab",
    "Min"
];

const days=computed(()=>{

    const firstDay=props.date.startOf("month");

    const start=firstDay.startOf("week").add(1,"day");

    return Array.from({length:42},(_,index)=>{

        return start.add(index,"day");

    });

});

</script>

<template>

<div class="calendar-grid">

    <div
        class="header"
        v-for="day in weekDays"
        :key="day"
    >

        {{ day }}

    </div>

    <div
        v-for="day in days"
        :key="day.toString()"
        class="cell"
        :class="{

            other:day.month()!=date.month(),

            today:day.isSame(dayjs(),'day')

        }"
    >

        <div class="number">

            {{ day.date() }}

        </div>

    </div>

</div>

</template>

<style scoped>

.calendar-grid{

display:grid;

grid-template-columns:repeat(7,1fr);

gap:1px;

background:#ddd;

}

.header{

background:#2563eb;

color:white;

padding:12px;

font-weight:bold;

text-align:center;

}

.cell{

background:white;

height:120px;

padding:8px;

}

.number{

font-weight:bold;

}

.other{

color:#bbb;

background:#fafafa;

}

.today{

outline:3px solid #2563eb;

}

</style>
