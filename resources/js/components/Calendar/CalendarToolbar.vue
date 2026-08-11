<script setup lang="ts">
defineProps<{
    title: string;
}>();

const emit = defineEmits(['previous', 'next', 'today']);
</script>

<template>
    <div class="toolbar">
        <div>
            <button @click="emit('previous')">◀</button>

            <button @click="emit('today')">Hari Ini</button>

            <button @click="emit('next')">▶</button>
        </div>

        <h2>
            {{ title }}
        </h2>
    </div>
</template>

<style scoped>
.toolbar {
    display: flex;

    justify-content: space-between;

    align-items: center;
}

button {
    padding: 8px 14px;

    margin-right: 8px;

    cursor: pointer;
}
</style>
