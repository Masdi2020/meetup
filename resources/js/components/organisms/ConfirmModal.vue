<script setup lang="ts">
import AppModal from '@/components/organisms/AppModal.vue';

withDefaults(
    defineProps<{
        title: string;
        message: string;
        confirmLabel?: string;
        tone?: 'danger' | 'primary';
        processing?: boolean;
    }>(),
    {
        confirmLabel: 'Konfirmasi',
        tone: 'danger',
        processing: false,
    },
);

const emit = defineEmits<{ close: []; confirm: [] }>();
</script>

<template>
    <AppModal :title="title" max-width="md" @close="emit('close')">
        <p class="text-sm leading-6 text-slate-600">{{ message }}</p>

        <template #actions>
            <button
                type="button"
                class="rounded-lg border px-4 py-2 text-slate-700 hover:bg-slate-100"
                :disabled="processing"
                @click="emit('close')"
            >
                Batal
            </button>
            <button
                type="button"
                class="rounded-lg px-4 py-2 text-white disabled:cursor-not-allowed disabled:opacity-60"
                :class="
                    tone === 'danger'
                        ? 'bg-red-600 hover:bg-red-700'
                        : 'bg-blue-600 hover:bg-blue-700'
                "
                :disabled="processing"
                @click="emit('confirm')"
            >
                {{ processing ? 'Memproses...' : confirmLabel }}
            </button>
        </template>
    </AppModal>
</template>
