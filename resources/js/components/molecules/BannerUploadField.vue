<script setup lang="ts">
import { X } from '@lucide/vue';
import { useObjectUrl } from '@vueuse/core';
import { useId } from 'vue';
import BookingFilePicker from '@/components/molecules/BookingFilePicker.vue';
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
const id = useId();
const preview = useObjectUrl(file);

function selectFile([selected]: File[]) {
    file.value = selected;
    emit('change');
}

function clearFile() {
    file.value = null;
    emit('change');
}
</script>

<template>
    <FormField
        label="Unggah Banner Rapat"
        :for-id="id"
        :appearance="appearance"
        :error="error"
    >
        <BookingFilePicker
            :id="id"
            :label="file ? 'Ganti banner' : 'Pilih gambar banner'"
            hint="JPG atau PNG · Maks. 5 MB"
            accept=".jpg,.jpeg,.png"
            :disabled="disabled"
            @select="selectFile"
        />
        <div v-if="preview" class="relative mt-3 w-fit max-w-[350px]">
            <img
                :src="preview"
                alt="Pratinjau banner rapat"
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
        <p
            v-if="file"
            class="mt-2 text-sm break-words text-slate-600"
            role="status"
        >
            {{ file.name }}
        </p>
    </FormField>
</template>
