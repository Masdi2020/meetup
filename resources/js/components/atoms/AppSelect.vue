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
select.app-control {
    min-height: var(--ui-control-height);
}
</style>
