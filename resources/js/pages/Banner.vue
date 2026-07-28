<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";

const props = defineProps<{
    booking: any;
    next_change: string | null;
}>();

const isFullscreen = ref(false);

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

async function toggleFullscreen() {
    try {
        if (!document.fullscreenElement) {
            await document.documentElement.requestFullscreen();
        } else {
            await document.exitFullscreen();
        }
    } catch (e) {
        console.error(e);
    }
}

function onFullscreenChange() {
    isFullscreen.value = !!document.fullscreenElement;
}

onMounted(() => {
    scheduleReload();
    document.addEventListener("fullscreenchange", onFullscreenChange);
});

onUnmounted(() => {
    if (timer) {
        clearTimeout(timer);
    }

    document.removeEventListener("fullscreenchange", onFullscreenChange);
});
</script>

<template>

    <button
        class="fullscreen-btn"
        @click="toggleFullscreen"
    >
        {{ isFullscreen ? "⤢" : "⛶" }}
    </button>

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

.fullscreen-btn {
    position: fixed;
    right: 24px;
    bottom: 24px;

    padding: 12px 16px;

    border: none;
    border-radius: 12px;

    background: rgba(0, 0, 0, 0.7);
    color: white;

    font-size: 24px;

    cursor: pointer;

    backdrop-filter: blur(8px);

    transition: 0.2s;
}

.fullscreen-btn:hover {
    background: rgba(255, 2555, 2555, 0.2);
}

.fullscreen-btn:active {
    transform: scale(0.95);
}
</style>
