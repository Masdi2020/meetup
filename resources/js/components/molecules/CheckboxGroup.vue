<script setup lang="ts">
import FormField from '@/components/molecules/FormField.vue';

defineProps<{
    label: string;
    options: Array<{ id: number; name: string }>;
}>();

const model = defineModel<number[]>({ default: () => [] });
</script>

<template>
    <FormField :label="label" appearance="admin">
        <div class="grid gap-2 md:grid-cols-2">
            <label
                v-for="option in options"
                :key="option.id"
                class="flex items-center gap-2 rounded-lg border p-2 text-sm"
            >
                <input v-model="model" :value="option.id" type="checkbox" />
                <span>{{ option.name }}</span>
            </label>
        </div>
    </FormField>
</template>
