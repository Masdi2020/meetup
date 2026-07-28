<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { onMounted, onUnmounted } from "vue";

const props = defineProps<{
    booking: any;
    next_change: string | null;
}>();

let timer: number | undefined;

function scheduleReload() {
    if (!props.next_change) {
        return;
    }

    const target = new Date(props.next_change).getTime();

    const delay = Math.max(
        target - Date.now(),
        1000
    );

    timer = window.setTimeout(() => {
        router.reload({
            only: [
                'booking',
                'next_change'
            ],
        });
    }, delay);
}

onMounted(scheduleReload);

onUnmounted(() => {
    if (timer) {
        clearTimeout(timer);
    }
});
</script>

<template>

<div class="screen">

    <img
        v-if="booking?.attachments?.length"
        :src="`/storage/${booking.attachments[0].path}`"
        class="banner"
    >

    <h1
        v-else-if="booking"
        class="title"
    >
        {{ booking.title }}
    </h1>

</div>

</template>

<style>
.screen{
    width:100vw;
    height:100vh;
    background:black;

    display:flex;
    justify-content:center;
    align-items:center;
}

.banner{

    width:100%;
    height:100%;

    object-fit:contain;
}

.title{

    color:white;

    font-size:72px;

    font-weight:bold;

    text-align:center;

    padding:40px;
}
</style>
