<script setup lang="ts">
defineOptions({ inheritAttrs: false });
const model = defineModel<string>({ default: '' });
</script>

<template>
    <textarea v-model="model" class="app-control" v-bind="$attrs" />
</template>

<style scoped>
textarea.app-control {
    min-height: 112px;
    resize: vertical;
}
</style>
