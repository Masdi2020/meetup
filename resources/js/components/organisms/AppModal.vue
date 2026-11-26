<script setup lang="ts">
import { X } from '@lucide/vue';
import { inject, onBeforeUnmount, onMounted, ref } from 'vue';
import type { Ref } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        maxWidth?: 'md' | 'lg' | 'xl' | '2xl' | '3xl';
        closable?: boolean;
    }>(),
    { maxWidth: 'lg', closable: true },
);

const emit = defineEmits<{ close: [] }>();
const sidebarOffset = inject<Ref<string>>('sidebarOffset', ref('0px'));
const dialog = ref<HTMLDialogElement | null>(null);

onMounted(() => dialog.value?.showModal());
onBeforeUnmount(() => dialog.value?.close());

function cancel() {
    if (props.closable) {
        emit('close');
    }
}
</script>

<template>
    <Teleport to="body">
        <dialog
            ref="dialog"
            class="fixed inset-y-0 right-0 m-0 h-dvh max-h-none w-auto max-w-none items-center justify-center bg-transparent p-4 transition-[left] duration-200 backdrop:bg-slate-950/40 open:flex"
            :style="{ left: sidebarOffset }"
            :aria-label="title"
            @cancel.prevent="cancel"
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
            >
                <header
                    class="mb-4 flex shrink-0 items-center justify-between gap-field"
                >
                    <h2 class="text-xl font-bold text-slate-900">
                        {{ title }}
                    </h2>
                    <button
                        v-if="closable"
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
                    class="mt-4 flex shrink-0 flex-col-reverse gap-action sm:flex-row sm:justify-end"
                >
                    <slot name="actions" />
                </footer>
            </section>
        </dialog>
    </Teleport>
</template>
