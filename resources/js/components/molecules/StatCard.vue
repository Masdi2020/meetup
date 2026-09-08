<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        value: string | number | null | undefined;
        tone?: 'default' | 'info' | 'indigo' | 'warning' | 'success';
        size?: 'default' | 'large';
        accent?: boolean;
    }>(),
    {
        tone: 'default',
        size: 'default',
        accent: false,
    },
);
</script>

<template>
    <article
        class="ui-card ui-card-body stat-card"
        :class="[
            { 'border-l-4': accent },
            {
                'border-blue-500': accent && tone === 'info',
                'border-indigo-500': accent && tone === 'indigo',
                'border-yellow-500': accent && tone === 'warning',
                'border-green-500': accent && tone === 'success',
            },
        ]"
    >
        <p
            :class="
                size === 'large' ? 'text-gray-500' : 'text-sm text-gray-500'
            "
        >
            {{ label }}
        </p>
        <p
            class="mt-2 font-bold"
            :class="[
                'text-3xl',
                {
                    'text-blue-600': tone === 'info',
                    'text-indigo-600': tone === 'indigo',
                    'text-yellow-600': tone === 'warning',
                    'text-green-600': tone === 'success',
                },
            ]"
        >
            {{ value ?? 0 }}
        </p>
    </article>
</template>

<style scoped>
.stat-card.border-l-4 {
    border-left-width: 4px;
}
.stat-card.border-blue-500 {
    border-left-color: #3b82f6;
}
.stat-card.border-indigo-500 {
    border-left-color: #6366f1;
}
.stat-card.border-yellow-500 {
    border-left-color: #eab308;
}
.stat-card.border-green-500 {
    border-left-color: #22c55e;
}
</style>
