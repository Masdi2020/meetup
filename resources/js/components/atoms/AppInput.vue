<script setup lang="ts">
defineOptions({ inheritAttrs: false });
withDefaults(defineProps<{ appearance?: 'default' | 'admin' }>(), {
    appearance: 'default',
});
const model = defineModel<string | number | null>({ default: '' });
</script>

<template>
    <input
        v-model="model"
        class="app-input"
        :class="`app-input--${appearance}`"
        v-bind="$attrs"
    />
</template>

<style scoped>
.app-input {
    box-sizing: border-box;
    width: 100%;
    border: 1px solid #d9d9d9;
    background: #fff;
    outline: 0;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}
.app-input--default {
    min-height: 48px;
    padding: 0 15px;
    border-radius: 10px;
    font-size: 15px;
}
.app-input--admin {
    min-height: 42px;
    padding: 8px 16px;
    border-color: #d1d5db;
    border-radius: 8px;
}
.app-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 12%);
}
</style>
