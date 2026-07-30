<script setup lang="ts">
import { ref } from 'vue';
import Sidebar from '@/components/Sidebar.vue';

const isOpen = ref(true);
</script>

<template>
    <div class="layout">
        <Sidebar :is-open="isOpen" @toggle="isOpen = !isOpen" />

        <main class="content" :class="{ collapsed: !isOpen }">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.layout {
    min-height: 100vh;
}

.content {
    margin-left: 230px;
    min-height: 100vh;
    background: #f5f5f5;
    padding: 30px;
    transition: margin-left 0.3s ease;
}

.content.collapsed {
    margin-left: 72px;
}
</style>
