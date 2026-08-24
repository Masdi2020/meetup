<script setup lang="ts">
defineOptions({ inheritAttrs: false });
defineProps<{ label: string }>();
const model = defineModel<boolean>({ default: false });
</script>

<template>
    <label
        class="inline-flex items-center gap-2 text-sm font-medium text-gray-700"
    >
        <input
            v-model="model"
            type="checkbox"
            class="rounded border-gray-300"
            v-bind="$attrs"
        />
        <span>{{ label }}</span>
    </label>
</template>
