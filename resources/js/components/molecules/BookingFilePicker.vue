<script setup lang="ts">
import { Upload } from '@lucide/vue';

defineProps<{
    id?: string;
    label: string;
    hint: string;
    accept: string;
    multiple?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits<{ select: [files: File[]] }>();

function selectFiles(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    input.value = '';

    if (files.length) {
        emit('select', files);
    }
}
</script>

<template>
    <label class="block">
        <input
            :id="id"
            :aria-label="label"
            type="file"
            :accept="accept"
            :multiple="multiple"
            :disabled="disabled"
            class="peer sr-only"
            @change="selectFiles"
        />
        <span
            class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-blue-300 bg-blue-50/60 px-4 py-4 text-left transition peer-focus-visible:ring-2 peer-focus-visible:ring-blue-600 peer-focus-visible:ring-offset-2 peer-disabled:cursor-not-allowed peer-disabled:opacity-60 hover:border-blue-500 hover:bg-blue-50"
        >
            <span
                class="grid size-10 shrink-0 place-items-center rounded-lg bg-white text-blue-700"
            >
                <Upload :size="19" aria-hidden="true" />
            </span>
            <span class="min-w-0">
                <span class="block text-sm font-semibold text-blue-800">{{
                    label
                }}</span>
                <span class="mt-0.5 block text-xs text-slate-600">{{
                    hint
                }}</span>
            </span>
        </span>
    </label>
</template>
