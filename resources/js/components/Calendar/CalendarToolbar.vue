<script setup lang="ts">
defineProps<{
    title: string;
}>();

const emit = defineEmits(['previous', 'next', 'today']);
</script>

<template>
    <div class="toolbar">
        <h2>{{ title }}</h2>

        <div class="navigation">
            <button @click="emit('previous')">◀</button>
            <button @click="emit('today')">Hari Ini</button>
            <button @click="emit('next')">▶</button>
        </div>
    </div>
</template>

<style scoped>
.toolbar {
    position: relative;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    min-height: 44px;
}

.toolbar h2 {
    margin: 0;
}

button {
    min-height: 40px;
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
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    white-space: nowrap;
}

@media (max-width: 640px) {
    .toolbar {
        align-items: center;
        flex-direction: column;
        gap: 12px;
    }
    .toolbar h2 {
        font-size: 18px;
    }
    .navigation {
        position: static;
        transform: none;
        justify-content: center;
    }
    button {
        min-height: 42px;
    }
}
</style>
