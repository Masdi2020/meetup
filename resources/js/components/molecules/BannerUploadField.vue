<script setup lang="ts">
import { X } from '@lucide/vue';
import { useObjectUrl } from '@vueuse/core';
import { ref, useId, watch } from 'vue';
import FormField from '@/components/molecules/FormField.vue';

withDefaults(
    defineProps<{
        error?: string;
        disabled?: boolean;
        appearance?: 'default' | 'admin';
    }>(),
    { appearance: 'default' },
);

const file = defineModel<File | null>({ required: true });
const emit = defineEmits<{ change: [] }>();
const input = ref<HTMLInputElement | null>(null);
const id = useId();
const preview = useObjectUrl(file);

function selectFile(event: Event) {
    file.value = (event.target as HTMLInputElement).files?.[0] ?? null;
    emit('change');
}

function clearFile() {
    file.value = null;
    emit('change');
}

watch(file, (value) => {
    if (!value && input.value) {
        input.value.value = '';
    }
});
</script>

<template>
    <FormField
        label="Unggah Banner Rapat"
        :for-id="id"
        :appearance="appearance"
        :error="error"
        hint="Format yang didukung: JPG, JPEG, PNG"
    >
        <input
            :id="id"
            ref="input"
            type="file"
            accept=".jpg,.jpeg,.png"
            class="w-full"
            :disabled="disabled"
            @change="selectFile"
        />
        <div v-if="preview" class="relative mt-3 w-fit max-w-[350px]">
            <img
                :src="preview"
                alt="Preview Banner"
                class="block w-full rounded-lg border object-cover"
            />
            <button
                type="button"
                class="absolute top-2 right-2 grid size-11 place-items-center rounded-full bg-gray-900/80 text-white hover:bg-red-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-50"
                aria-label="Batalkan unggahan banner"
                :disabled="disabled"
                @click="clearFile"
            >
                <X :size="18" :stroke-width="2.5" aria-hidden="true" />
            </button>
        </div>
    </FormField>
</template>
