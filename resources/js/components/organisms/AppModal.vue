<script setup lang="ts">
import { X } from '@lucide/vue';

withDefaults(
    defineProps<{
        title: string;
        maxWidth?: 'md' | 'lg' | 'xl' | '2xl' | '3xl';
    }>(),
    { maxWidth: 'lg' },
);

const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4"
            role="presentation"
            @click.self="emit('close')"
        >
            <section
                class="w-full rounded-2xl bg-white p-6 shadow-xl"
                :class="{
                    'max-w-md': maxWidth === 'md',
                    'max-w-lg': maxWidth === 'lg',
                    'max-w-xl': maxWidth === 'xl',
                    'max-w-2xl': maxWidth === '2xl',
                    'max-w-3xl': maxWidth === '3xl',
                }"
                role="dialog"
                aria-modal="true"
                :aria-label="title"
            >
                <header class="mb-5 flex items-center justify-between gap-4">
                    <h2 class="text-xl font-bold text-slate-900">
                        {{ title }}
                    </h2>
                    <button
                        type="button"
                        class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Tutup dialog"
                        @click="emit('close')"
                    >
                        <X :size="20" />
                    </button>
                </header>

                <div class="max-h-[calc(100vh-11rem)] overflow-y-auto">
                    <slot />
                </div>
                <footer
                    v-if="$slots.actions"
                    class="mt-6 flex justify-end gap-3"
                >
                    <slot name="actions" />
                </footer>
            </section>
        </div>
    </Teleport>
</template>
