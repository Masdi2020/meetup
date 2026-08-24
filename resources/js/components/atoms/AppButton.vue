<script setup lang="ts">
withDefaults(
    defineProps<{ variant?: 'primary' | 'secondary'; block?: boolean }>(),
    { variant: 'primary', block: false },
);
</script>

<template>
    <button
        class="app-button"
        :class="[`app-button--${variant}`, { 'app-button--block': block }]"
    >
        <slot />
    </button>
</template>

<style scoped>
.app-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 24px;
    border: 0;
    border-radius: 999px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition:
        background-color 0.2s,
        opacity 0.2s,
        transform 0.2s;
}
.app-button--primary {
    color: #fff;
    background: #4d8df7;
}
.app-button--primary:hover:not(:disabled) {
    background: #397cf0;
}
.app-button--secondary {
    color: #fff;
    background: #1f3768;
}
.app-button--secondary:hover:not(:disabled) {
    background: #2a4b90;
}
.app-button--block {
    width: 100%;
}
.app-button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}
.app-button:active:not(:disabled) {
    transform: scale(0.98);
}
</style>
