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
    min-width: 0;
    min-height: var(--ui-control-height);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius);
    padding: 10px 12px;
    background: white;
    outline: none;
}
.app-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 12%);
}
@media (max-width: 768px) {
    .app-control {
        font-size: 16px;
    }
}
</style>
