<script setup lang="ts">
defineProps<{
    title: string;
    view: 'month' | 'week' | 'day';
}>();

const emit = defineEmits<{
    previous: [];
    next: [];
    today: [];
    'update:view': [view: 'month' | 'week' | 'day'];
}>();
</script>

<template>
    <div class="calendar-toolbar">
        <h2>{{ title }}</h2>
        <div class="navigation">
            <button aria-label="Sebelumnya" @click="emit('previous')">
                &lsaquo;
            </button>
            <button @click="emit('today')">Hari Ini</button>
            <button aria-label="Berikutnya" @click="emit('next')">
                &rsaquo;
            </button>
        </div>
        <div class="view-switch" aria-label="Pilihan tampilan kalender">
            <button
                :class="{ active: view === 'month' }"
                :aria-pressed="view === 'month'"
                @click="emit('update:view', 'month')"
            >
                Bulan
            </button>
            <button
                :class="{ active: view === 'week' }"
                :aria-pressed="view === 'week'"
                @click="emit('update:view', 'week')"
            >
                Minggu
            </button>
            <button
                :class="{ active: view === 'day' }"
                :aria-pressed="view === 'day'"
                @click="emit('update:view', 'day')"
            >
                Hari
            </button>
        </div>
    </div>
</template>

<style scoped>
.calendar-toolbar {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    min-height: 44px;
    gap: 8px;
}
.calendar-toolbar h2 {
    margin: 0;
    border: 0;
    font-size: clamp(16px, 2vw, 22px);
    color: #173b7a;
}
button {
    min-height: var(--ui-control-height);
    padding: 8px 14px;
    border: 1px solid #cbd5e1;
    background: #fff;
    cursor: pointer;
}
button:first-child {
    border-radius: 8px 0 0 8px;
}
button:last-child {
    border-radius: 0 8px 8px 0;
}
button + button {
    border-left: 0;
}
button:hover {
    background: #eff6ff;
}
.navigation {
    justify-self: center;
    display: flex;
    white-space: nowrap;
}
.view-switch {
    display: flex;
    justify-self: end;
}
.view-switch button.active {
    border-color: #2563eb;
    background: #2563eb;
    color: #fff;
}
@container (max-width: 760px) {
    .calendar-toolbar {
        grid-template-columns: minmax(0, 1fr);
        gap: 6px;
    }
    .calendar-toolbar h2 {
        font-size: 16px;
        text-align: center;
    }
    .navigation {
        margin-left: 0;
        position: static;
        transform: none;
        justify-content: center;
    }
    .view-switch {
        justify-self: center;
    }
    button {
        min-height: var(--ui-control-height);
        padding: 6px 8px;
        font-size: 14px;
    }
}
</style>
