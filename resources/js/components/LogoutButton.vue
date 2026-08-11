<script setup lang="ts">
import { router } from '@inertiajs/vue3';

function logout() {
    router.post('/logout');
}
</script>

<template>
    <button class="logout-button" @click="logout">Keluar</button>
</template>

<style scoped>
.logout-button {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    background: white;
    color: #111827;
    cursor: pointer;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 10px;
}

.logout-button:hover {
    background: #f3f4f6;
}
</style>
