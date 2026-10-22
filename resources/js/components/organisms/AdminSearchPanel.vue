<script setup lang="ts">
import { computed } from 'vue';
import AppButton from '@/components/atoms/AppButton.vue';
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
        'xl:grid-cols-[minmax(0,1fr)_minmax(0,220px)_auto_auto]':
            props.filterColumns === 1,
        'xl:grid-cols-[minmax(0,1fr)_minmax(0,180px)_minmax(0,220px)_auto_auto]':
            props.filterColumns === 2,
    };
});
</script>

<template>
    <section class="ui-card ui-card-body" aria-label="Pencarian">
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

            <AppButton type="submit" variant="primary" size="sm">
                Cari
            </AppButton>

            <AppButton
                type="button"
                variant="neutral"
                size="sm"
                @click="emit('reset')"
            >
                Reset
            </AppButton>
        </form>

        <div v-if="$slots.footer" class="mt-3">
            <slot name="footer" />
        </div>
    </section>
</template>
