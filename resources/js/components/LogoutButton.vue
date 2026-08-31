<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { LogOut } from '@lucide/vue';

withDefaults(defineProps<{ compact?: boolean }>(), { compact: false });

function logout() {
    router.post('/logout');
}
</script>

<template>
    <button
        type="button"
        class="logout-button"
        :class="{ 'logout-button--compact': compact }"
        :title="compact ? 'Keluar' : undefined"
        aria-label="Keluar dari aplikasi"
        @click="logout"
    >
        <LogOut :size="18" :stroke-width="2" />
        <span v-if="!compact">Keluar</span>
    </button>
</template>

<style scoped>
.logout-button {
    display: flex;
    width: 100%;
    min-height: 42px;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border: 1px solid rgb(255 255 255 / 10%);
    border-radius: 11px;
    background: rgb(255 255 255 / 6%);
    color: #c5d5f1;
    cursor: pointer;
    font-size: 13px;
    font-weight: 650;
    text-align: left;
    transition:
        border-color 0.2s ease,
        background 0.2s ease,
        color 0.2s ease;
}

.logout-button:hover {
    border-color: rgb(248 113 113 / 25%);
    background: rgb(239 68 68 / 13%);
    color: #fecaca;
}

.logout-button--compact {
    width: 52px;
    height: 46px;
    justify-content: center;
    margin: 0 auto;
    padding: 0;
}
</style>
