<script setup lang="ts">
import { ref } from 'vue';
import Sidebar from '@/components/admin/Sidebar.vue';

const isOpen = ref(true);
</script>

<template>
    <div class="admin-layout">
        <Sidebar :is-open="isOpen" @toggle="isOpen = !isOpen" />

        <main class="admin-content" :class="{ collapsed: !isOpen }">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.admin-layout {
    min-height: 100vh;
}

.admin-content {
    margin-left: 230px;
    min-height: 100vh;
    background: #f5f5f5;
    padding: 30px;
    transition: margin-left 0.3s ease;
}

.admin-content.collapsed {
    margin-left: 72px;
}
</style>
