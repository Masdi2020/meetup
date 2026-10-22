<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <header class="page-header">
        <div class="page-heading">
            <h1 class="page-title">{{ title }}</h1>
            <p v-if="description" class="text-gray-500">{{ description }}</p>
        </div>
        <slot />
    </header>
</template>
