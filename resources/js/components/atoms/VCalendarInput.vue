<script setup lang="ts">
import { CalendarDays } from '@lucide/vue';
import { DatePicker } from 'v-calendar';
import { computed, watch } from 'vue';

type TimeParts = {
    hours: number;
    minutes: number;
    seconds: number;
    milliseconds: number;
};

type TimeRule =
    | number
    | number[]
    | { min?: number; max?: number; interval?: number }
    | ((part: number, parts: TimeParts) => boolean);

export type VCalendarTimeRules = {
    hours?: TimeRule;
    minutes?: TimeRule;
    seconds?: TimeRule;
    milliseconds?: TimeRule;
};

const props = withDefaults(
    defineProps<{
        mode?: 'date' | 'time';
        appearance?: 'default' | 'admin';
        placeholder?: string;
        minDate?: string;
        maxDate?: string;
        rules?: VCalendarTimeRules;
        disabled?: boolean;
        invalid?: boolean;
    }>(),
    {
        mode: 'date',
        appearance: 'default',
        placeholder: '',
        disabled: false,
        invalid: false,
    },
);

const model = defineModel<string>({ default: '' });

const pickerModel = computed({
    get: () => model.value || null,
    set: (value: string | null) => {
        model.value = value ?? '';
    },
});

function matchesRule(
    value: number,
    rule: TimeRule | undefined,
    parts: TimeParts,
): boolean {
    if (rule === undefined) {
        return true;
    }

    if (typeof rule === 'number') {
        return value === rule;
    }

    if (typeof rule === 'function') {
        return rule(value, parts);
    }

    if (Array.isArray(rule)) {
        return rule.includes(value);
    }

    return (
        (rule.min === undefined || value >= rule.min) &&
        (rule.max === undefined || value <= rule.max) &&
        (rule.interval === undefined || value % rule.interval === 0)
    );
}

function initializeTime() {
    if (props.mode !== 'time' || props.disabled || model.value) {
        return;
    }

    // VCalendar disables its time controls when its model has no valid date.
    // Seed the visible controls once the current availability has loaded.
    for (let hours = 0; hours < 24; hours++) {
        for (let minutes = 0; minutes < 60; minutes += 15) {
            const parts = { hours, minutes, seconds: 0, milliseconds: 0 };

            if (
                matchesRule(hours, props.rules?.hours, parts) &&
                matchesRule(minutes, props.rules?.minutes, parts)
            ) {
                model.value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;

                return;
            }
        }
    }
}

watch(
    [() => props.mode, () => props.disabled, () => props.rules, model],
    initializeTime,
    { immediate: true, flush: 'post' },
);

const masks = computed(() => ({
    modelValue: props.mode === 'date' ? 'YYYY-MM-DD' : 'HH:mm',
    input: props.mode === 'date' ? ['DD/MM/YYYY', 'YYYY-MM-DD'] : ['HH:mm'],
    inputTime24hr: ['HH:mm'],
}));

const fallbackPlaceholder = computed(() =>
    props.mode === 'date' ? 'Pilih tanggal' : 'Pilih jam',
);
</script>

<template>
    <fieldset
        v-if="mode === 'time'"
        class="calendar-input calendar-input--inline-time"
        :class="[
            `calendar-input--${appearance}`,
            {
                'calendar-input--disabled': disabled,
                'calendar-input--invalid': invalid,
            },
        ]"
        :disabled="disabled"
        :aria-invalid="invalid"
    >
        <DatePicker
            v-if="!disabled"
            v-model.string="pickerModel"
            mode="time"
            :masks="masks"
            :rules="rules"
            :popover="false"
            locale="id-ID"
            color="blue"
            is24hr
            hide-time-header
        />
        <span v-else class="calendar-input__empty-time">
            {{ model || '-- : --' }}
        </span>
    </fieldset>
    <DatePicker
        v-else
        v-model.string="pickerModel"
        :mode="mode"
        :masks="masks"
        :min-date="minDate"
        :max-date="maxDate"
        :rules="rules"
        :popover="{ visibility: 'click', placement: 'bottom-start' }"
        locale="id-ID"
        color="blue"
        :is-required="Boolean(model)"
    >
        <template #default="{ inputValue, inputEvents }">
            <div
                class="calendar-input"
                :class="[
                    `calendar-input--${appearance}`,
                    {
                        'calendar-input--disabled': disabled,
                        'calendar-input--invalid': invalid,
                    },
                ]"
            >
                <input
                    :value="inputValue"
                    class="calendar-input__control"
                    type="text"
                    :placeholder="placeholder || fallbackPlaceholder"
                    :disabled="disabled"
                    readonly
                    v-on="disabled ? {} : inputEvents"
                />
                <CalendarDays
                    class="calendar-input__icon"
                    :size="18"
                    aria-hidden="true"
                />
            </div>
        </template>
    </DatePicker>
</template>

<style scoped>
.calendar-input {
    position: relative;
    display: flex;
    width: 100%;
    min-width: 0;
    align-items: center;
    overflow: hidden;
    border: 1px solid var(--ui-border);
    background: #fff;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.calendar-input--default {
    min-height: var(--ui-control-height);
    border-radius: var(--ui-radius);
}

.calendar-input--admin {
    min-height: var(--ui-control-height);
    border-radius: var(--ui-radius);
}

.calendar-input--inline-time {
    min-width: 0;
    margin: 0;
    padding: 4px;
    justify-content: center;
}

.calendar-input--inline-time :deep(.vc-time-picker) {
    padding: 0;
}

.calendar-input--inline-time :deep(.vc-time-select-group) {
    border: 0;
    background: transparent;
}

.calendar-input__empty-time {
    color: #6b7280;
    font-size: 14px;
}

.calendar-input:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 12%);
}

.calendar-input--invalid {
    border-color: #dc3545;
    background: #fff5f5;
}

.calendar-input--disabled {
    cursor: not-allowed;
    background: #f3f4f6;
    opacity: 0.65;
}

.calendar-input__control {
    width: 100%;
    min-width: 0;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: #111827;
    cursor: pointer;
}

.calendar-input--default .calendar-input__control {
    min-height: 42px;
    padding: 0 44px 0 14px;
    font-size: 14px;
}

.calendar-input--admin .calendar-input__control {
    min-height: 42px;
    padding: 8px 42px 8px 16px;
    font-size: 14px;
}

.calendar-input__control:disabled {
    cursor: not-allowed;
}

.calendar-input__icon {
    position: absolute;
    right: 14px;
    color: #6b7280;
    pointer-events: none;
}
@media (max-width: 768px) {
    .calendar-input .calendar-input__control {
        font-size: 16px;
    }
}
</style>
