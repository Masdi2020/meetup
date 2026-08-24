<script setup lang="ts">
defineOptions({ inheritAttrs: false });
const model = defineModel<string | number | null>({ default: '' });
</script>

<template>
    <select v-model="model" class="app-control" v-bind="$attrs">
        <slot />
    </select>
</template>

<style scoped>
.app-control {
    box-sizing: border-box;
    width: 100%;
    min-height: 42px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 16px;
    background: white;
    outline: none;
}
.app-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 12%);
}
</style>
