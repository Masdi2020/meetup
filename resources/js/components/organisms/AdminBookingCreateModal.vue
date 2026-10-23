<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, toRef, watch } from 'vue';
import {
    AppButton,
    AppInput,
    AppSelect,
    AppTextarea,
    VCalendarInput,
} from '@/components/atoms';
import { BannerUploadField, FormField } from '@/components/molecules';
import AppModal from '@/components/organisms/AppModal.vue';
import { useBookingAvailability } from '@/composables/useBookingAvailability';
import { store } from '@/routes/admin/bookings';
import type {
    AdminBookingStatusOption,
    BookingStatus,
    BorrowerOption,
    BookingRoomOption,
} from '@/types/admin';

const props = defineProps<{
    open: boolean;
    rooms?: BookingRoomOption[];
    users?: BorrowerOption[];
    statuses?: AdminBookingStatusOption[];
}>();
const emit = defineEmits<{ close: [] }>();
const showAddBookingSuccess = ref(false);
const addBookingSubmitted = ref(false);

const addBookingForm = useForm({
    user_id: null as number | null,
    room_id: null as number | null,
    date: '',
    start_time: '',
    end_time: '',
    title: '',
    participants: null as number | null,
    request: '',
    status: 'pending' as BookingStatus,
    banner: null as File | null,
});

const addAvailability = useBookingAvailability({
    roomId: toRef(addBookingForm, 'room_id'),
    date: toRef(addBookingForm, 'date'),
    startTime: toRef(addBookingForm, 'start_time'),
    endTime: toRef(addBookingForm, 'end_time'),
    allowPast: true,
});

const addBookingHasWarning = (field: string): boolean => {
    if (!addBookingSubmitted.value) {
        return false;
    }

    switch (field) {
        case 'user_id':
            return addBookingForm.user_id === null;
        case 'room_id':
            return addBookingForm.room_id === null;
        case 'date':
            return !addBookingForm.date;
        case 'start_time':
        case 'end_time':
        case 'title':
            return !addBookingForm[field];
        case 'participants':
            return (
                !addBookingForm.participants || addBookingForm.participants < 1
            );
        default:
            return false;
    }
};

function closeAddBookingModal() {
    emit('close');
    showAddBookingSuccess.value = false;
    addBookingForm.reset();
    addBookingSubmitted.value = false;
}

function submitAddBooking() {
    addBookingSubmitted.value = true;

    if (
        addBookingHasWarning('user_id') ||
        addBookingHasWarning('room_id') ||
        addBookingHasWarning('date') ||
        addBookingHasWarning('start_time') ||
        addBookingHasWarning('end_time') ||
        addBookingHasWarning('title') ||
        addBookingHasWarning('participants')
    ) {
        return;
    }

    addBookingForm.post(store.url(), {
        forceFormData: true,
        onSuccess: () => {
            showAddBookingSuccess.value = true;
            addBookingForm.reset();
            addBookingSubmitted.value = false;
        },
    });
}

const selectedRoomHasDisplay = computed(
    () =>
        props.rooms?.find((room) => room.id === addBookingForm.room_id)
            ?.has_display ?? false,
);

watch(selectedRoomHasDisplay, (hasDisplay) => {
    if (!hasDisplay) {
        addBookingForm.banner = null;
        addBookingForm.clearErrors('banner');
    }
});
</script>

<template>
    <AppModal
        v-if="open"
        title="Tambah Peminjaman"
        max-width="3xl"
        @close="closeAddBookingModal"
    >
        <div
            v-if="showAddBookingSuccess"
            class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"
        >
            Peminjaman berhasil ditambahkan.
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <FormField
                label="Nama Peminjam"
                appearance="admin"
                required
                :error="
                    addBookingForm.errors.user_id ||
                    (addBookingHasWarning('user_id')
                        ? 'Nama peminjam wajib dipilih.'
                        : '')
                "
            >
                <AppSelect v-model="addBookingForm.user_id">
                    <option :value="null" disabled hidden>
                        Pilih Peminjam
                    </option>
                    <option
                        v-for="user in users"
                        :key="user.id"
                        :value="user.id"
                    >
                        {{ user.name
                        }}{{
                            user.role === 'super_admin'
                                ? ' (Super Admin)'
                                : user.role === 'admin'
                                  ? ' (Admin)'
                                  : ''
                        }}
                    </option>
                </AppSelect>
            </FormField>
            <FormField
                label="Status"
                appearance="admin"
                required
                :error="addBookingForm.errors.status"
            >
                <AppSelect v-model="addBookingForm.status">
                    <option
                        v-for="status in statuses"
                        :key="status.code"
                        :value="status.code"
                    >
                        {{ status.label }}
                    </option>
                </AppSelect>
            </FormField>
            <FormField
                label="Ruangan"
                appearance="admin"
                required
                :error="
                    addBookingForm.errors.room_id ||
                    (addBookingHasWarning('room_id')
                        ? 'Ruangan wajib dipilih.'
                        : '')
                "
            >
                <AppSelect v-model="addBookingForm.room_id">
                    <option :value="null" disabled hidden>Pilih Ruangan</option>
                    <option
                        v-for="room in rooms"
                        :key="room.id"
                        :value="room.id"
                    >
                        {{ room.name }}
                    </option>
                </AppSelect>
            </FormField>
            <FormField
                label="Tanggal"
                appearance="admin"
                required
                :error="
                    addBookingForm.errors.date ||
                    (addBookingHasWarning('date') ? 'Tanggal wajib diisi.' : '')
                "
            >
                <VCalendarInput
                    v-model="addBookingForm.date"
                    appearance="admin"
                    mode="date"
                />
            </FormField>
            <div class="grid min-w-0 grid-cols-2 gap-3 md:col-span-2">
                <FormField
                    label="Dari Jam"
                    appearance="admin"
                    required
                    :error="
                        addBookingForm.errors.start_time ||
                        (addBookingHasWarning('start_time')
                            ? 'Waktu mulai wajib diisi.'
                            : '')
                    "
                >
                    <VCalendarInput
                        v-model="addBookingForm.start_time"
                        appearance="admin"
                        mode="time"
                        :rules="addAvailability.startRules.value"
                        :disabled="
                            !addBookingForm.room_id ||
                            !addBookingForm.date ||
                            addAvailability.isLoading.value ||
                            Boolean(addAvailability.loadError.value) ||
                            addAvailability.availableStartTimes.value.length ===
                                0
                        "
                    />
                </FormField>
                <FormField
                    label="Sampai Jam"
                    appearance="admin"
                    required
                    :error="
                        addBookingForm.errors.end_time ||
                        (addBookingHasWarning('end_time')
                            ? 'Waktu selesai wajib diisi.'
                            : '')
                    "
                >
                    <VCalendarInput
                        v-model="addBookingForm.end_time"
                        appearance="admin"
                        mode="time"
                        :rules="addAvailability.endRules.value"
                        :disabled="
                            !addBookingForm.start_time ||
                            addAvailability.isLoading.value ||
                            Boolean(addAvailability.loadError.value) ||
                            addAvailability.availableEndTimes.value.length === 0
                        "
                    />
                </FormField>
            </div>
            <p
                v-if="addAvailability.isLoading.value"
                class="-mt-2 text-sm text-gray-500 md:col-span-2"
            >
                Memuat jam yang tersedia...
            </p>
            <p
                v-else-if="addAvailability.loadError.value"
                class="-mt-2 text-sm text-red-600 md:col-span-2"
            >
                {{ addAvailability.loadError.value }}
            </p>
            <p
                v-else-if="
                    addBookingForm.room_id &&
                    addBookingForm.date &&
                    addAvailability.availableStartTimes.value.length === 0
                "
                class="-mt-2 text-sm text-red-600 md:col-span-2"
            >
                Tidak ada jam yang tersedia pada tanggal ini.
            </p>
            <p
                v-else-if="addAvailability.bookedIntervals.value.length"
                class="-mt-2 text-sm text-gray-500 md:col-span-2"
            >
                Jam yang sudah terbooking disembunyikan dari pilihan.
            </p>
            <FormField
                label="Judul Kegiatan"
                appearance="admin"
                required
                :error="
                    addBookingForm.errors.title ||
                    (addBookingHasWarning('title') ? 'Judul wajib diisi.' : '')
                "
            >
                <AppInput v-model="addBookingForm.title" appearance="admin" />
            </FormField>
            <FormField
                label="Jumlah Orang"
                appearance="admin"
                required
                :error="
                    addBookingForm.errors.participants ||
                    (addBookingHasWarning('participants')
                        ? 'Jumlah orang wajib diisi dan minimal 1.'
                        : '')
                "
            >
                <AppInput
                    v-model="addBookingForm.participants"
                    appearance="admin"
                    type="number"
                    min="1"
                />
            </FormField>
            <FormField
                class="md:col-span-2"
                label="Permintaan Khusus"
                appearance="admin"
                :error="addBookingForm.errors.request"
            >
                <AppTextarea v-model="addBookingForm.request" rows="3" />
            </FormField>
            <BannerUploadField
                v-if="selectedRoomHasDisplay"
                v-model="addBookingForm.banner"
                appearance="admin"
                class="md:col-span-2"
                :error="addBookingForm.errors.banner"
                :disabled="addBookingForm.processing"
                @change="addBookingForm.clearErrors('banner')"
            />
            <div class="flex justify-end gap-3 md:col-span-2">
                <AppButton
                    type="button"
                    variant="neutral"
                    size="sm"
                    @click="closeAddBookingModal"
                >
                    Batal
                </AppButton>
                <AppButton
                    type="button"
                    variant="primary"
                    size="sm"
                    :disabled="addBookingForm.processing"
                    @click="submitAddBooking"
                >
                    {{ addBookingForm.processing ? 'Menyimpan...' : 'Simpan' }}
                </AppButton>
            </div>
        </div>
    </AppModal>
</template>
