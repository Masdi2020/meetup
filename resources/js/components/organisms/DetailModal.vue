<script setup lang="ts">
import AppModal from '@/components/organisms/AppModal.vue';

withDefaults(
    defineProps<{
        title: string;
        maxWidth?: 'md' | 'lg' | 'xl' | '2xl' | '3xl';
        closeLabel?: string;
    }>(),
    {
        maxWidth: 'lg',
        closeLabel: 'Tutup',
    },
);

const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <AppModal :title="title" :max-width="maxWidth" @close="emit('close')">
        <slot />

        <template #actions>
            <button
                type="button"
                class="rounded-lg border px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                @click="emit('close')"
            >
                {{ closeLabel }}
            </button>
        </template>
    </AppModal>
</template>
