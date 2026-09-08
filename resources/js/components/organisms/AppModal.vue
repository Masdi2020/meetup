<script setup lang="ts">
import { X } from '@lucide/vue';
import { inject, ref } from 'vue';
import type { Ref } from 'vue';

withDefaults(
    defineProps<{
        title: string;
        maxWidth?: 'md' | 'lg' | 'xl' | '2xl' | '3xl';
    }>(),
    { maxWidth: 'lg' },
);

const emit = defineEmits<{ close: [] }>();
const sidebarOffset = inject<Ref<string>>('sidebarOffset', ref('0px'));
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-y-0 right-0 z-[1100] flex items-center justify-center bg-slate-950/40 p-4 transition-[left] duration-200"
            :style="{ left: sidebarOffset }"
            role="presentation"
            @click.self="emit('close')"
        >
            <section
                class="ui-card-body flex max-h-[calc(100dvh-2rem)] w-full min-w-0 flex-col rounded-xl bg-white shadow-xl"
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
                <header
                    class="mb-4 flex shrink-0 items-center justify-between gap-4"
                >
                    <h2 class="text-xl font-bold text-slate-900">
                        {{ title }}
                    </h2>
                    <button
                        type="button"
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Tutup dialog"
                        @click="emit('close')"
                    >
                        <X :size="20" />
                    </button>
                </header>

                <div class="min-h-0 min-w-0 overflow-y-auto overscroll-contain">
                    <slot />
                </div>
                <footer
                    v-if="$slots.actions"
                    class="mt-4 flex shrink-0 flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                >
                    <slot name="actions" />
                </footer>
            </section>
        </div>
    </Teleport>
</template>
