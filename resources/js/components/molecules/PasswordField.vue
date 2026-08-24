<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { ref } from 'vue';
import AppInput from '@/components/atoms/AppInput.vue';
import FormField from '@/components/molecules/FormField.vue';
defineProps<{
    label: string;
    error?: string;
    required?: boolean;
    placeholder?: string;
}>();
const model = defineModel<string>({ default: '' });
const visible = ref(false);
</script>

<template>
    <FormField :label="label" :error="error" :required="required"
        ><div class="password-field">
            <AppInput
                v-model="model"
                :type="visible ? 'text' : 'password'"
                :placeholder="placeholder"
                :required="required"
            /><button
                type="button"
                class="password-field__toggle"
                :aria-label="
                    visible ? 'Sembunyikan password' : 'Tampilkan password'
                "
                @click="visible = !visible"
            >
                <EyeOff v-if="visible" :size="20" /><Eye v-else :size="20" />
            </button></div
    ></FormField>
</template>

<style scoped>
.password-field {
    position: relative;
}
.password-field :deep(.app-input) {
    padding-right: 46px;
}
.password-field__toggle {
    position: absolute;
    top: 50%;
    right: 12px;
    display: inline-flex;
    padding: 0;
    border: 0;
    background: transparent;
    color: #4d8df7;
    cursor: pointer;
    transform: translateY(-50%);
}
</style>
