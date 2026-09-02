<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useEchoPublic } from '@laravel/echo-vue';
import { ArrowLeft, Maximize, Minimize } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    booking: any;
    next_change: string | null;
    now: string;
}>();

const isFullscreen = ref(false);

let timer: number | undefined;

const currentTime = ref(new Date(props.now));

let clock: number | undefined;

const currentClock = computed(() =>
    currentTime.value.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }),
);

const remainingTime = computed(() => {
    if (!props.booking) {
        return '';
    }

    const end = new Date(
        `${currentTime.value.toISOString().slice(0, 10)}T${props.booking.end_time}`,
    );

    const diff = end.getTime() - currentTime.value.getTime();

    if (diff <= 0) {
        return 'Selesai';
    }

    const hours = Math.floor(diff / 3600000);
    const minutes = Math.floor((diff % 3600000) / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);

    if (hours > 0) {
        return `${hours} jam ${minutes} menit`;
    }

    return `${minutes} menit ${seconds} detik`;
});

function reloadBanner() {
    router.reload({
        only: ['booking', 'next_change', 'now'],
        onFinish: scheduleReload,
    });
}

function backToDisplay() {
    router.visit('/display');
}

function scheduleReload() {
    if (timer) {
        clearTimeout(timer);
    }

    if (!props.next_change) {
        return;
    }

    const target = new Date(props.next_change).getTime();
    const delay = Math.max(target - Date.now(), 1000);

    timer = window.setTimeout(reloadBanner, delay);
}

function refreshWhenVisible() {
    if (!document.hidden) {
        reloadBanner();
    }
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

useEchoPublic('meeting-banner', '.banner.updated', reloadBanner);

onMounted(() => {
    scheduleReload();

    clock = window.setInterval(() => {
        currentTime.value = new Date(currentTime.value.getTime() + 1000);
    }, 1000);

    document.addEventListener('fullscreenchange', onFullscreenChange);
    document.addEventListener('visibilitychange', refreshWhenVisible);
});

watch(
    () => props.now,
    (now) => {
        currentTime.value = new Date(now);
    },
);

watch(
    () => props.next_change,
    () => scheduleReload(),
);

onUnmounted(() => {
    if (timer) {
        clearTimeout(timer);
    }

    if (clock) {
        clearInterval(clock);
    }

    document.removeEventListener('fullscreenchange', onFullscreenChange);
    document.removeEventListener('visibilitychange', refreshWhenVisible);
});
</script>

<template>
    <button
        type="button"
        class="back-btn"
        aria-label="Kembali ke halaman display"
        title="Kembali ke halaman display"
        @click="backToDisplay"
    >
        <ArrowLeft :size="24" />
    </button>
    <button
        type="button"
        class="fullscreen-btn"
        :aria-label="
            isFullscreen ? 'Keluar dari layar penuh' : 'Tampilkan layar penuh'
        "
        :title="
            isFullscreen ? 'Keluar dari layar penuh' : 'Tampilkan layar penuh'
        "
        @click="toggleFullscreen"
    >
        <Minimize v-if="isFullscreen" :size="24" aria-hidden="true" />
        <Maximize v-else :size="24" aria-hidden="true" />
    </button>

    <div class="screen">
        <img
            v-if="booking?.attachments?.length"
            :src="`/storage/${booking.attachments[0].path}`"
            class="banner"
        />

        <div v-else-if="booking" class="meeting-info">
            <h1 class="title">
                {{ booking.title }}
            </h1>

            <p class="time">
                {{ booking.start_time.slice(0, 5) }} -
                {{ booking.end_time.slice(0, 5) }}
            </p>

            <p class="clock">
                {{ currentClock }}
            </p>

            <p class="remaining">Berakhir dalam {{ remainingTime }}</p>
        </div>

        <video
            v-else
            src="/storage/videos/Video tampilan kosong.mp4"
            autoplay
            muted
            loop
            playsinline
        />
    </div>
</template>

<style>
.back-btn {
    position: fixed;
    top: 24px;
    left: 24px;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border: none;
    border-radius: 12px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    backdrop-filter: blur(8px);
    transition: 0.2s;
}

.back-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.back-btn:active {
    transform: scale(0.95);
}
.screen {
    width: 100vw;
    height: 100vh;
    background: black;

    display: flex;
    justify-content: center;
    align-items: center;
}

.banner {
    width: 100%;
    height: 100%;

    object-fit: contain;
}

.meeting-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    padding: 40px;
}

.title {
    font-size: 72px;
    font-weight: bold;
    margin-bottom: 24px;
}

.time {
    font-size: 42px;
    font-weight: 500;
    opacity: 0.9;
    margin-bottom: 16px;
}

.fullscreen-btn {
    position: fixed;
    right: 24px;
    bottom: 24px;

    display: flex;
    width: 48px;
    height: 48px;
    align-items: center;
    justify-content: center;

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
    background: rgba(255, 255, 255, 0.2);
}

.fullscreen-btn:active {
    transform: scale(0.95);
}
</style>
