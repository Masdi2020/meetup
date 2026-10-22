<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?:
            | 'primary'
            | 'secondary'
            | 'neutral'
            | 'outline'
            | 'warning'
            | 'danger'
            | 'success';
        size?: 'sm' | 'md' | 'lg';
        block?: boolean;
    }>(),
    { variant: 'primary', size: 'md', block: false },
);
</script>

<template>
    <button
        class="app-button"
        :class="[
            `app-button--${variant}`,
            `app-button--${size}`,
            { 'app-button--block': block },
        ]"
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
    padding: 10px 16px;
    border: 0;
    border-radius: var(--ui-radius);
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
    background: var(--ui-primary);
}
.app-button--primary:hover:not(:disabled) {
    background: var(--ui-primary-hover);
}
.app-button--secondary {
    color: #fff;
    background: #1f3768;
}
.app-button--secondary:hover:not(:disabled) {
    background: #2a4b90;
}
.app-button--neutral {
    border: 1px solid var(--ui-border);
    background: white;
    color: #374151;
}
.app-button--neutral:hover:not(:disabled) {
    background: #f3f4f6;
}
.app-button--outline {
    border: 1px solid var(--ui-primary);
    background: white;
    color: var(--ui-primary);
}
.app-button--outline:hover:not(:disabled) {
    background: #eff6ff;
}
.app-button--warning {
    background: #eab308;
    color: white;
}
.app-button--warning:hover:not(:disabled) {
    background: #ca8a04;
}
.app-button--danger {
    background: #dc2626;
    color: white;
}
.app-button--danger:hover:not(:disabled) {
    background: #b91c1c;
}
.app-button--success {
    background: #16a34a;
    color: white;
}
.app-button--success:hover:not(:disabled) {
    background: #15803d;
}
.app-button--sm {
    padding: 8px 16px;
    font-size: 14px;
}
.app-button--lg {
    padding: 12px 20px;
}
.app-button:focus-visible {
    outline: 2px solid var(--ui-primary);
    outline-offset: 2px;
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
