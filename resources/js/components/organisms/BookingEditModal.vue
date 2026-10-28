<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, toRef } from 'vue';
import { AppButton, AppInput, VCalendarInput } from '@/components/atoms';
import { FormField } from '@/components/molecules';
import AppModal from '@/components/organisms/AppModal.vue';
import { useBookingAvailability } from '@/composables/useBookingAvailability';
import { bookingDateForInput, localDateString } from '@/lib/dates';
import type { BookingEdit } from '@/types/booking';

const props = defineProps<{
    booking: BookingEdit;
    updateUrl: (id: number) => string;
}>();
const emit = defineEmits<{ close: [] }>();
const today = localDateString();
const form = useForm({
    title: props.booking.title,
    date: bookingDateForInput(props.booking.date),
    start_time: props.booking.start_time,
    end_time: props.booking.end_time,
});
const bookingError = computed(
    () => (form.errors as Record<string, string>).booking,
);
const availability = useBookingAvailability({
    roomId: computed(() => props.booking.room_id),
    date: toRef(form, 'date'),
    startTime: toRef(form, 'start_time'),
    endTime: toRef(form, 'end_time'),
    ignoreBookingId: computed(() => props.booking.id),
});

function close() {
    if (!form.processing) {
        emit('close');
    }
}

function submit() {
    form.put(props.updateUrl(props.booking.id), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <AppModal title="Edit Booking" max-width="2xl" @close="close">
        <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
            <FormField
                class="md:col-span-2"
                label="Judul Kegiatan"
                appearance="admin"
                required
                :error="form.errors.title"
            >
                <AppInput v-model="form.title" appearance="admin" />
            </FormField>
            <FormField
                label="Tanggal"
                appearance="admin"
                required
                :error="form.errors.date"
            >
                <VCalendarInput
                    v-model="form.date"
                    appearance="admin"
                    mode="date"
                    :min-date="today"
                />
            </FormField>
            <div class="hidden md:block" />
            <div class="grid min-w-0 grid-cols-2 gap-3 md:col-span-2">
                <FormField
                    label="Dari Jam"
                    appearance="admin"
                    required
                    :error="form.errors.start_time"
                >
                    <VCalendarInput
                        v-model="form.start_time"
                        appearance="admin"
                        mode="time"
                        :rules="availability.startRules.value"
                        :disabled="
                            availability.isLoading.value ||
                            Boolean(availability.loadError.value) ||
                            availability.availableStartTimes.value.length === 0
                        "
                    />
                </FormField>
                <FormField
                    label="Sampai Jam"
                    appearance="admin"
                    required
                    :error="form.errors.end_time"
                >
                    <VCalendarInput
                        v-model="form.end_time"
                        appearance="admin"
                        mode="time"
                        :rules="availability.endRules.value"
                        :disabled="
                            !form.start_time ||
                            availability.isLoading.value ||
                            Boolean(availability.loadError.value) ||
                            availability.availableEndTimes.value.length === 0
                        "
                    />
                </FormField>
            </div>
            <p
                v-if="availability.isLoading.value"
                class="-mt-2 text-sm text-gray-500 md:col-span-2"
            >
                Memuat jam yang tersedia...
            </p>
            <p
                v-else-if="availability.loadError.value"
                class="-mt-2 text-sm text-red-600 md:col-span-2"
            >
                {{ availability.loadError.value }}
            </p>
            <p
                v-else-if="availability.bookedIntervals.value.length"
                class="-mt-2 text-sm text-gray-500 md:col-span-2"
            >
                Jam booking lain disembunyikan dari pilihan.
            </p>
            <p v-if="bookingError" class="text-sm text-red-600 md:col-span-2">
                {{ bookingError }}
            </p>
            <div class="flex justify-end gap-3 md:col-span-2">
                <AppButton
                    type="button"
                    variant="neutral"
                    size="sm"
                    :disabled="form.processing"
                    @click="close"
                >
                    Batal
                </AppButton>
                <AppButton
                    type="submit"
                    variant="primary"
                    size="sm"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </AppButton>
            </div>
        </form>
    </AppModal>
</template>
