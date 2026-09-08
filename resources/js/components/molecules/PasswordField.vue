<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppInput from '@/components/atoms/AppInput.vue';
import FormField from '@/components/molecules/FormField.vue';

withDefaults(
    defineProps<{
        label: string;
        error?: string;
        required?: boolean;
        placeholder?: string;
        id?: string;
        autocomplete?: string;
        appearance?: 'default' | 'admin';
    }>(),
    { appearance: 'default' },
);

const model = defineModel<string>({ default: '' });
const visible = ref(false);
const password = computed({
    get: () => model.value,
    set: (value: string) => {
        model.value = value.replace(/\s/g, '');
    },
});
</script>

<template>
    <FormField
        :label="label"
        :error="error"
        :required="required"
        :appearance="appearance"
        :for-id="id"
    >
        <div class="password-field">
            <AppInput
                :id="id"
                v-model="password"
                :type="visible ? 'text' : 'password'"
                :placeholder="placeholder"
                :required="required"
                :autocomplete="autocomplete"
                :appearance="appearance"
                @keydown.space.prevent
            />
            <button
                type="button"
                class="password-field__toggle"
                :aria-label="
                    visible ? 'Sembunyikan password' : 'Tampilkan password'
                "
                @click="visible = !visible"
            >
                <EyeOff v-if="visible" :size="20" />
                <Eye v-else :size="20" />
            </button>
        </div>
    </FormField>
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
    right: 2px;
    width: 40px;
    height: 40px;
    align-items: center;
    justify-content: center;
    display: inline-flex;
    padding: 0;
    border: 0;
    background: transparent;
    color: #4d8df7;
    cursor: pointer;
    transform: translateY(-50%);
}
</style>
