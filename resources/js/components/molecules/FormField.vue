<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        error?: string;
        hint?: string;
        required?: boolean;
        appearance?: 'default' | 'admin';
    }>(),
    { appearance: 'default' },
);
</script>

<template>
    <div class="form-field" :class="`form-field--${appearance}`">
        <label class="form-field__label">
            {{ label }}<span v-if="required" aria-hidden="true"> *</span>
        </label>
        <slot />
        <p v-if="error" class="form-field__error">{{ error }}</p>
        <p v-else-if="hint" class="form-field__hint">{{ hint }}</p>
    </div>
</template>

<style scoped>
.form-field {
    text-align: left;
}
.form-field--default {
    margin-bottom: 18px;
}
.form-field__label {
    display: block;
    margin-bottom: 8px;
    color: #2c3e50;
    font-size: 14px;
    font-weight: 600;
}
.form-field--admin .form-field__label {
    color: #374151;
}
.form-field__label span,
.form-field__error {
    color: #dc2626;
}
.form-field__error,
.form-field__hint {
    margin: 6px 0 0;
    font-size: 13px;
}
.form-field__hint {
    color: #6b7280;
}
</style>
