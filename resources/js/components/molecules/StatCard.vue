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
        class="rounded-xl bg-white shadow"
        :class="[
            size === 'large' ? 'p-6' : 'p-5',
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
                size === 'large' ? 'text-4xl' : 'text-3xl',
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
