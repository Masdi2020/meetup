<script setup lang="ts">
import { CalendarDays, Clock3 } from '@lucide/vue';
import { computed, ref, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });
const props = withDefaults(
    defineProps<{
        appearance?: 'default' | 'admin';
        type?: string;
        min?: string | number;
        max?: string | number;
        step?: string | number;
        disabled?: boolean;
        invalid?: boolean;
    }>(),
    {
        appearance: 'default',
        type: 'text',
        disabled: false,
        invalid: false,
    },
);
const model = defineModel<string | number | null>({ default: '' });
const attrs = useAttrs();
const inputRef = ref<HTMLInputElement | null>(null);
const isPicker = computed(() => ['date', 'month', 'time'].includes(props.type));
const inputAttrs = computed(() =>
    Object.fromEntries(
        Object.entries(attrs).filter(
            ([key]) => key !== 'class' && key !== 'style',
        ),
    ),
);
const formattedValue = computed(() => {
    const value = String(model.value ?? '');

    if (!value) {
        return {
            date: 'dd/mm/yyyy',
            month: 'mm/yyyy',
            time: '--:--',
        }[props.type];
    }

    if (props.type === 'date') {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

        return match ? `${match[3]}/${match[2]}/${match[1]}` : value;
    }

    if (props.type === 'month') {
        const match = /^(\d{4})-(\d{2})$/.exec(value);

        return match ? `${match[2]}/${match[1]}` : value;
    }

    return value.slice(0, 5);
});

function openPicker() {
    if (props.disabled || !inputRef.value) {
        return;
    }

    try {
        inputRef.value.showPicker();
    } catch {
        inputRef.value.focus();
    }
}
</script>

<template>
    <div
        v-if="isPicker"
        class="app-picker"
        :class="[
            `app-picker--${appearance}`,
            {
                'app-picker--disabled': disabled,
                'app-picker--invalid': invalid,
            },
            $attrs.class,
        ]"
        :style="$attrs.style"
    >
        <span
            class="app-picker__value"
            :class="{ 'app-picker__value--placeholder': !model }"
            aria-hidden="true"
        >
            {{ formattedValue }}
        </span>
        <Clock3
            v-if="type === 'time'"
            class="app-picker__icon"
            :size="18"
            aria-hidden="true"
        />
        <CalendarDays
            v-else
            class="app-picker__icon"
            :size="18"
            aria-hidden="true"
        />
        <input
            ref="inputRef"
            v-model="model"
            class="app-picker__native"
            :type="type"
            :min="min"
            :max="max"
            :step="step"
            :disabled="disabled"
            v-bind="inputAttrs"
            @click="openPicker"
        />
    </div>
    <input
        v-else
        v-model="model"
        class="app-input"
        :class="`app-input--${appearance}`"
        :type="type"
        :min="min"
        :max="max"
        :step="step"
        :disabled="disabled"
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
.app-picker {
    position: relative;
    display: flex;
    box-sizing: border-box;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    overflow: hidden;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #111827;
    cursor: pointer;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}
.app-picker--default {
    min-height: 48px;
    padding: 0 15px;
    border-radius: 10px;
    font-size: 15px;
}
.app-picker--admin {
    min-height: 42px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
}
.app-picker:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 12%);
}
.app-picker--invalid {
    border-color: #dc3545;
    background: #fff5f5;
}
.app-picker--disabled {
    cursor: not-allowed;
    background: #f3f4f6;
    opacity: 0.65;
}
.app-picker__value {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.app-picker__value--placeholder,
.app-picker__icon {
    color: #6b7280;
}
.app-picker__icon {
    flex: 0 0 auto;
}
.app-picker__native {
    position: absolute;
    z-index: 1;
    inset: 0;
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    border: 0;
    opacity: 0;
    cursor: pointer;
}
.app-picker__native:disabled {
    cursor: not-allowed;
}
</style>
