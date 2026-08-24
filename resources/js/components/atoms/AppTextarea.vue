<script setup lang="ts">
defineOptions({ inheritAttrs: false });
const model = defineModel<string>({ default: '' });
</script>

<template>
    <textarea v-model="model" class="app-control" v-bind="$attrs" />
</template>

<style scoped>
.app-control {
    box-sizing: border-box;
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 16px;
    background: white;
    outline: none;
    resize: vertical;
}
.app-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 12%);
}
</style>
