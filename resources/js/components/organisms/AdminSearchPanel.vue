<script setup lang="ts">
import { computed } from 'vue';
import AppInput from '@/components/atoms/AppInput.vue';

const props = withDefaults(
    defineProps<{
        placeholder: string;
        filterColumns?: 0 | 1 | 2;
    }>(),
    { filterColumns: 0 },
);

const search = defineModel<string>({ required: true });
const emit = defineEmits<{
    search: [];
    reset: [];
}>();

const gridClass = computed(() => {
    return {
        'md:grid-cols-[minmax(0,1fr)_auto_auto]': props.filterColumns === 0,
        'lg:grid-cols-[minmax(0,1fr)_220px_auto_auto]':
            props.filterColumns === 1,
        'lg:grid-cols-[minmax(0,1fr)_180px_220px_auto_auto]':
            props.filterColumns === 2,
    };
});
</script>

<template>
    <section class="rounded-xl bg-white p-5 shadow" aria-label="Pencarian">
        <form
            class="grid gap-4"
            :class="gridClass"
            role="search"
            @submit.prevent="emit('search')"
        >
            <AppInput
                v-model="search"
                appearance="admin"
                type="search"
                :placeholder="placeholder"
                class="rounded-lg border px-4 py-2 outline-none focus:border-blue-500"
            />

            <slot name="filters" />

            <button
                type="submit"
                class="rounded-lg bg-blue-600 px-5 py-2 text-white transition hover:bg-blue-700"
            >
                Cari
            </button>

            <button
                type="button"
                class="rounded-lg border px-5 py-2 transition hover:bg-gray-100"
                @click="emit('reset')"
            >
                Reset
            </button>
        </form>

        <div v-if="$slots.footer" class="mt-3">
            <slot name="footer" />
        </div>
    </section>
</template>
