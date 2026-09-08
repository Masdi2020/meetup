<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, toRef, watch } from 'vue';
import VCalendarInput from '@/components/atoms/VCalendarInput.vue';
import AppModal from '@/components/organisms/AppModal.vue';
import {
    localDateString,
    useBookingAvailability,
} from '@/composables/useBookingAvailability';
import type { BookingRoom } from '@/types/room';

const today = localDateString();

const preview = ref<string | null>(null);
const bannerInput = ref<HTMLInputElement | null>(null);

const showSuccessDialog = ref(false);

const { rooms } = defineProps<{
    rooms: BookingRoom[];
}>();

const form = useForm({
    room_id: null as number | null,
    date: '',
    start_time: '',
    end_time: '',
    title: '',
    participants: null as number | null,
    request: '',
    banner: null as File | null,
});

const submitted = ref(false);

const hasWarning = (field: string): boolean => {
    if (form.errors[field as keyof typeof form.errors]) {
        return true;
    }

    if (!submitted.value) {
        return false;
    }

    switch (field) {
        case 'room_id':
            return form.room_id === null;
        case 'date':
            return !form.date || form.date < today;
        case 'start_time':
            return !form.start_time;
        case 'end_time':
            return !form.end_time || isInvalidTimeRange.value;
        case 'title':
            return !form[field];
        case 'participants':
            return !form.participants || form.participants < 1;
        default:
            return false;
    }
};

const {
    bookedIntervals,
    isLoading: isLoadingAvailability,
    loadError: availabilityError,
    availableStartTimes,
    availableEndTimes,
    startRules,
    endRules,
} = useBookingAvailability({
    roomId: toRef(form, 'room_id'),
    date: toRef(form, 'date'),
    startTime: toRef(form, 'start_time'),
    endTime: toRef(form, 'end_time'),
});

const handleFile = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (!target.files?.length) {
        return;
    }

    const file = target.files[0];

    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    form.banner = file;
    preview.value = URL.createObjectURL(file);
};

const clearBanner = () => {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    form.banner = null;
    preview.value = null;
    form.clearErrors('banner');

    if (bannerInput.value) {
        bannerInput.value.value = '';
    }
};

const submitBooking = () => {
    submitted.value = true;

    if (
        hasWarning('room_id') ||
        hasWarning('date') ||
        hasWarning('start_time') ||
        hasWarning('end_time') ||
        hasWarning('title') ||
        hasWarning('participants') ||
        isInvalidTimeRange.value
    ) {
        return;
    }

    form.post('/booking', {
        forceFormData: true,
        onSuccess: () => {
            showSuccessDialog.value = true;

            form.reset();
            clearBanner();
            submitted.value = false;
        },
    });
};

const isInvalidTimeRange = computed(() => {
    if (!form.start_time || !form.end_time) {
        return false;
    }

    return form.end_time <= form.start_time;
});

const selectedRoom = computed(() => {
    return rooms.find((room) => room.id === form.room_id);
});

const hasDisplay = computed(() => {
    return selectedRoom.value?.has_display ?? false;
});

onMounted(() => {
    const savedRoomId = localStorage.getItem('booking_room_id');

    if (!savedRoomId) {
        return;
    }

    const roomId = Number(savedRoomId);

    const roomExists = rooms.some((room) => room.id === roomId);

    if (roomExists) {
        form.room_id = roomId;
    } else {
        localStorage.removeItem('booking_room_id');
        form.room_id = null;
    }
});

watch(
    () => form.room_id,
    () => {
        form.clearErrors('room_id');
    },
);

watch([() => form.room_id, () => form.date], () => {
    form.start_time = '';
    form.end_time = '';
});

watch(
    () => form.room_id,
    (roomId) => {
        if (roomId !== null) {
            localStorage.setItem('booking_room_id', String(roomId));
        }
    },
);

watch(hasDisplay, (roomHasDisplay) => {
    if (!roomHasDisplay && form.banner) {
        clearBanner();
    }
});

onBeforeUnmount(() => {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }
});
</script>

<template>
    <div class="app-page booking-page">
        <header class="page-header page-heading">
            <h1 class="page-title">Formulir Peminjaman</h1>
        </header>

        <div class="page-card">
            <form
                class="booking-card ui-card ui-card-body"
                novalidate
                @submit.prevent="submitBooking"
            >
                <fieldset
                    class="room-selector"
                    :class="{ warning: hasWarning('room_id') }"
                >
                    <legend>Ruangan <span class="required">*</span></legend>
                    <div class="room-options">
                        <label
                            v-for="room in rooms"
                            :key="room.id"
                            class="room-option"
                        >
                            <input
                                v-model="form.room_id"
                                type="radio"
                                name="room_id"
                                :value="room.id"
                                :aria-invalid="hasWarning('room_id')"
                                :aria-describedby="
                                    hasWarning('room_id')
                                        ? 'room-error'
                                        : undefined
                                "
                            />
                            {{ room.name }}
                        </label>
                    </div>
                    <p
                        v-if="form.errors.room_id"
                        id="room-error"
                        class="warning-text"
                    >
                        {{ form.errors.room_id }}
                    </p>
                    <p
                        v-else-if="hasWarning('room_id')"
                        id="room-error"
                        class="warning-text"
                    >
                        Ruangan wajib dipilih.
                    </p>
                </fieldset>

                <div
                    class="form-group"
                    :class="{ warning: hasWarning('date') }"
                >
                    <label> Tanggal <span class="required">*</span> </label>
                    <VCalendarInput
                        v-model="form.date"
                        mode="date"
                        :min-date="today"
                        :invalid="hasWarning('date')"
                    />
                    <p
                        v-if="form.date && form.date < today"
                        class="warning-text"
                    >
                        Tanggal tidak boleh kurang dari hari ini.
                    </p>
                    <p v-else-if="hasWarning('date')" class="warning-text">
                        Tanggal wajib diisi.
                    </p>
                </div>

                <div class="booking-time-row">
                    <div
                        class="form-group"
                        :class="{ warning: hasWarning('start_time') }"
                    >
                        <label>
                            Dari Jam <span class="required">*</span>
                        </label>
                        <VCalendarInput
                            v-model="form.start_time"
                            mode="time"
                            :rules="startRules"
                            :disabled="
                                !form.room_id ||
                                !form.date ||
                                isLoadingAvailability ||
                                Boolean(availabilityError) ||
                                availableStartTimes.length === 0
                            "
                            :invalid="hasWarning('start_time')"
                        />
                        <p v-if="form.errors.start_time" class="warning-text">
                            {{ form.errors.start_time }}
                        </p>
                        <p
                            v-else-if="hasWarning('start_time')"
                            class="warning-text"
                        >
                            Waktu mulai wajib dipilih.
                        </p>
                    </div>

                    <div
                        class="form-group"
                        :class="{ warning: hasWarning('end_time') }"
                    >
                        <label>
                            Sampai Jam <span class="required">*</span>
                        </label>
                        <VCalendarInput
                            v-model="form.end_time"
                            mode="time"
                            :rules="endRules"
                            :disabled="
                                !form.start_time ||
                                isLoadingAvailability ||
                                Boolean(availabilityError) ||
                                availableEndTimes.length === 0
                            "
                            :invalid="hasWarning('end_time')"
                        />

                        <p v-if="isInvalidTimeRange" class="warning-text">
                            Waktu selesai harus lebih besar dari waktu mulai
                        </p>

                        <p v-if="form.errors.end_time" class="warning-text">
                            {{ form.errors.end_time }}
                        </p>

                        <p
                            v-else-if="hasWarning('end_time')"
                            class="warning-text"
                        >
                            Waktu selesai wajib dipilih.
                        </p>
                    </div>
                </div>

                <p v-if="isLoadingAvailability" class="availability-message">
                    Memuat jam yang tersedia...
                </p>
                <p v-else-if="availabilityError" class="warning-text">
                    {{ availabilityError }} Muat ulang halaman untuk mencoba
                    lagi.
                </p>
                <p
                    v-else-if="
                        form.room_id &&
                        form.date &&
                        availableStartTimes.length === 0
                    "
                    class="warning-text"
                >
                    Tidak ada jam yang tersedia pada tanggal ini.
                </p>
                <p
                    v-else-if="
                        form.room_id && form.date && bookedIntervals.length
                    "
                    class="availability-message"
                >
                    Jam yang sudah terbooking otomatis disembunyikan dari
                    pilihan.
                </p>

                <div
                    class="form-group"
                    :class="{ warning: hasWarning('title') }"
                >
                    <label> Judul Rapat <span class="required">*</span> </label>
                    <input type="text" v-model="form.title" />
                    <p v-if="hasWarning('title')" class="warning-text">
                        Judul rapat wajib diisi.
                    </p>
                </div>

                <div
                    class="form-group"
                    :class="{ warning: hasWarning('participants') }"
                >
                    <label>
                        Jumlah Orang <span class="required">*</span>
                    </label>
                    <input
                        type="number"
                        min="1"
                        v-model.number="form.participants"
                    />
                    <p v-if="hasWarning('participants')" class="warning-text">
                        Jumlah orang wajib diisi dan minimal 1.
                    </p>
                </div>

                <div class="form-group">
                    <label>Permintaan Khusus</label>
                    <textarea
                        rows="3"
                        placeholder="contoh: penataan meja dan kursi"
                        v-model="form.request"
                    ></textarea>
                </div>

                <div class="form-group" v-if="hasDisplay">
                    <label>Unggah Banner Rapat</label>

                    <input
                        ref="bannerInput"
                        type="file"
                        accept=".jpg,.jpeg,.png"
                        @change="handleFile"
                    />

                    <small>Format yang didukung: JPG, JPEG, PNG</small>

                    <div v-if="preview" class="banner-preview">
                        <img :src="preview" alt="Preview Banner" />
                        <button
                            type="button"
                            class="banner-remove"
                            aria-label="Batalkan unggahan banner"
                            title="Batalkan unggahan"
                            @click="clearBanner"
                        >
                            <X :size="18" :stroke-width="2.5" />
                        </button>
                    </div>

                    <div v-if="form.errors.banner" class="error">
                        {{ form.errors.banner }}
                    </div>
                </div>

                <div class="button-wrapper">
                    <button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Booking' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <AppModal
        v-if="showSuccessDialog"
        title="Booking Berhasil Diajukan"
        max-width="md"
        @close="showSuccessDialog = false"
    >
        <div class="text-center">
            <div class="dialog-icon" aria-hidden="true">?</div>
            <p>
                Permintaan peminjaman ruangan telah berhasil dikirim dan sedang
                menunggu persetujuan admin.
            </p>
        </div>
        <template #actions
            ><button type="button" @click="showSuccessDialog = false">
                Tutup
            </button></template
        >
    </AppModal>
</template>

<style scoped>
.room-selector {
    min-width: 0;
    margin: 0 0 var(--ui-gap);
    padding: 0;
    border: 0;
}

.room-selector legend {
    margin-bottom: 8px;
}

.room-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.room-option {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    background: var(--ui-surface);
    border: 1px solid var(--ui-border);
    border-radius: 8px;
    padding: 8px 12px;
    font-weight: 600;
    cursor: pointer;
}

.room-option input {
    width: 18px;
    height: 18px;
    padding: 0;
    accent-color: #173b7a;
}

.booking-card {
    width: 100%;
    max-width: var(--ui-form-width);
}

.form-group {
    display: flex;
    flex-direction: column;
    min-width: 0;
    margin-bottom: var(--ui-gap);
}

.booking-time-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 6px;
    color: #2c2c2c;
}

input:not([type='radio']),
select,
textarea {
    width: 100%;
    min-height: var(--ui-control-height);
    padding: 10px 12px;
    border: 1px solid var(--ui-border);
    border-radius: 8px;
    background: white;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group.warning input,
.form-group.warning select,
.room-selector.warning .room-option,
.form-group.warning textarea {
    border: 1px solid #dc3545;
    background: #fff5f5;
}

.required {
    color: #dc3545;
}

.warning-text {
    margin-top: 6px;
    color: #dc3545;
    font-size: 13px;
}

.availability-message {
    margin: -8px 0 18px;
    color: #475569;
    font-size: 13px;
}

textarea {
    resize: vertical;
}

.button-wrapper {
    display: flex;
    justify-content: flex-end;
}

button {
    min-height: var(--ui-control-height);
    background: var(--ui-primary);
    color: white;
    border: none;
    border-radius: var(--ui-radius);
    padding: 10px 16px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.2s;
}

button:hover {
    background: var(--ui-primary-hover);
}

@media (max-width: 768px) {
    input:not([type='radio']),
    textarea {
        font-size: 16px;
    }
    .booking-time-row {
        grid-template-columns: minmax(0, 1fr);
    }
}

.banner-preview {
    position: relative;
    width: fit-content;
    max-width: 350px;
    margin-top: 15px;
}

.banner-preview img {
    display: block;
    width: 100%;
    max-width: 350px;
    border-radius: 10px;
    border: 1px solid #ddd;
    object-fit: cover;
}

.banner-remove {
    position: absolute;
    top: 8px;
    right: 8px;
    display: grid;
    width: 36px;
    height: 36px;
    min-height: 36px;
    padding: 0;
    place-items: center;
    border: 0;
    border-radius: 9999px;
    color: white;
    background: rgba(17, 24, 39, 0.82);
    cursor: pointer;
}

.banner-remove:hover {
    background: #dc2626;
}

.banner-remove:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}

.error {
    margin-top: 5px;
    color: #dc3545;
    font-size: 13px;
}

.dialog-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: #28a745;
    color: white;

    margin: 0 auto 20px;

    display: flex;
    justify-content: center;
    align-items: center;

    font-size: 32px;
    font-weight: bold;
}
</style>
