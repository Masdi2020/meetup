<script setup lang="ts">
import {
    CalendarCheck,
    CalendarOff,
    CalendarX,
    CircleStop,
    Eye,
    Pencil,
    RotateCcwKey,
    Trash2,
} from '@lucide/vue';
import type { Component } from 'vue';

type Action =
    | 'detail'
    | 'edit'
    | 'approve'
    | 'cancel'
    | 'reject'
    | 'delete'
    | 'finish'
    | 'reset';

const props = defineProps<{
    action: Action;
    label?: string;
}>();

const actions: Record<
    Action,
    { icon: Component; label: string; classes: string }
> = {
    detail: {
        icon: Eye,
        label: 'Detail',
        classes: 'border border-gray-300 text-gray-600 hover:bg-gray-100',
    },
    edit: {
        icon: Pencil,
        label: 'Edit',
        classes: 'bg-amber-500 text-white hover:bg-amber-600',
    },
    approve: {
        icon: CalendarCheck,
        label: 'Approve',
        classes: 'bg-green-600 text-white hover:bg-green-700',
    },
    cancel: {
        icon: CalendarOff,
        label: 'Cancel',
        classes: 'bg-red-600 text-white hover:bg-red-700',
    },
    reject: {
        icon: CalendarX,
        label: 'Reject',
        classes: 'bg-red-600 text-white hover:bg-red-700',
    },
    delete: {
        icon: Trash2,
        label: 'Hapus',
        classes: 'bg-red-700 text-white hover:bg-red-800',
    },
    finish: {
        icon: CircleStop,
        label: 'Akhiri Sekarang',
        classes: 'bg-blue-600 text-white hover:bg-blue-700',
    },
    reset: {
        icon: RotateCcwKey,
        label: 'Reset Password',
        classes: 'bg-indigo-600 text-white hover:bg-indigo-700',
    },
};
</script>

<template>
    <button
        type="button"
        :aria-label="props.label ?? actions[action].label"
        :title="props.label ?? actions[action].label"
        :class="actions[action].classes"
        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg transition focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
    >
        <component
            :is="actions[action].icon"
            class="h-4.5 w-4.5"
            aria-hidden="true"
        />
    </button>
</template>
