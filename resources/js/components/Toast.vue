<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { toast } = useToast();
</script>

<template>
    <Transition name="toast-fade">
        <div v-if="toast" :class="['toast', toast.type]">
            {{ toast.message }}
        </div>
    </Transition>
</template>

<style scoped>
.toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 50;
    padding: 14px 18px;
    border-radius: 12px;
    color: white;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
}

.toast.success {
    background: #16a34a;
}

.toast.error {
    background: #dc2626;
}

.toast-fade-enter-active,
.toast-fade-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.toast-fade-enter-from,
.toast-fade-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>
