<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';

interface Room {
    id: number;
    name: string;
}

const today = new Date().toISOString().split('T')[0];

const preview = ref<string | null>(null);

const showSuccessDialog = ref(false);

const { rooms } = defineProps<{
    rooms: Room[];
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
    if (!submitted.value) {
        return false;
    }

    switch (field) {
        case 'room_id':
            return form.room_id === null;
        case 'date':
            return !form.date || form.date < today;
        case 'start_time':
        case 'end_time':
        case 'title':
            return !form[field];
        case 'participants':
            return !form.participants || form.participants < 1;
        default:
            return false;
    }
};

const minStartTime = computed(() => {
    if (form.date !== today) {
        return '07:00';
    }

    const now = new Date();

    let hour = now.getHours();
    let minute = now.getMinutes();

    if (minute > 0 && minute <= 30) {
        minute = 30;
    } else if (minute > 30) {
        hour++;
        minute = 0;
    }

    return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
});

const minEndTime = computed(() => {
    return form.start_time || minStartTime.value;
});

const handleFile = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (!target.files?.length) {
        return;
    }

    const file = target.files[0];
    form.banner = file;
    preview.value = URL.createObjectURL(file);
};

const submitBooking = () => {
    submitted.value = true;

    if (
        hasWarning('room_id') ||
        hasWarning('date') ||
        hasWarning('start_time') ||
        hasWarning('end_time') ||
        hasWarning('title') ||
        hasWarning('participants')
    ) {
        return;
    }

    form.post('/booking', {
        forceFormData: true,
        onSuccess: () => {
            showSuccessDialog.value = true;

            form.reset();
            preview.value = null;
            submitted.value = false;
        },
        onError: (err) => {
            console.log(err);
        },
    });
};

onMounted(() => {
    const savedRoomId = localStorage.getItem('booking_room_id');

    if (savedRoomId) {
        form.room_id = Number(savedRoomId);
    }
});

watch(
    () => form.room_id,
    (roomId) => {
        if (roomId !== null) {
            localStorage.setItem('booking_room_id', String(roomId));
        }
    },
);
</script>

<template>
    <div class="booking-page">
        <h2>Formulir Peminjaman</h2>

        <div class="page-card">
            <div
                class="room-selector"
                :class="{ warning: hasWarning('room_id') }"
            >
                <label> Ruangan <span class="required">*</span> </label>
                <select v-model.number="form.room_id">
                    <option :value="null" disabled hidden>Pilih Ruangan</option>
                    <option
                        v-for="room in rooms"
                        :key="room.id"
                        :value="room.id"
                    >
                        {{ room.name }}
                    </option>
                </select>
                <p v-if="hasWarning('room_id')" class="warning-text">
                    Ruangan wajib dipilih.
                </p>
            </div>

            <div class="booking-card">
                <div
                    class="form-group"
                    :class="{ warning: hasWarning('date') }"
                >
                    <label> Tanggal <span class="required">*</span> </label>
                    <input type="date" v-model="form.date" :min="today" />
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

                <div
                    class="form-group"
                    :class="{ warning: hasWarning('start_time') }"
                >
                    <label> Dari Jam <span class="required">*</span> </label>
                    <input
                        type="time"
                        v-model="form.start_time"
                        :min="minStartTime"
                        max="23:59"
                    />
                    <p v-if="hasWarning('start_time')" class="warning-text">
                        Waktu mulai wajib diisi.
                    </p>
                </div>

                <div
                    class="form-group"
                    :class="{ warning: hasWarning('end_time') }"
                >
                    <label> Sampai Jam <span class="required">*</span> </label>
                    <input
                        type="time"
                        v-model="form.end_time"
                        :min="minEndTime"
                        max="23:59"
                    />
                    <p v-if="hasWarning('end_time')" class="warning-text">
                        Waktu selesai wajib diisi.
                    </p>
                </div>

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

                <div class="form-group" v-if="form.room_id === 1">
                    <label>Unggah Banner Rapat</label>

                    <input
                        type="file"
                        accept=".jpg,.jpeg,.png"
                        @change="handleFile"
                    />

                    <small>Format yang didukung: JPG, JPEG, PNG</small>

                    <div v-if="preview" class="banner-preview">
                        <img :src="preview" alt="Preview Banner" />
                    </div>

                    <div v-if="form.errors.banner" class="error">
                        {{ form.errors.banner }}
                    </div>
                </div>

                <div class="button-wrapper">
                    <form @submit.prevent="submitBooking">
                        <button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Booking' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div
        v-if="showSuccessDialog"
        class="dialog-overlay"
        @click.self="showSuccessDialog = false"
    >
        <div class="dialog">
            <div class="dialog-icon">✓</div>

            <h3>Booking Berhasil Diajukan</h3>

            <p>
                Permintaan peminjaman ruangan telah berhasil dikirim dan sedang
                menunggu persetujuan admin.
            </p>

            <button @click="showSuccessDialog = false">Tutup</button>
        </div>
    </div>
</template>

<style scoped>
.booking-page {
    width: 100%;
    margin: 0;
    padding: 30px;
}

h2 {
    font-size: 24px;
    margin-bottom: 15px;
    color: #173b7a;
    border-bottom: 2px solid #d9d9d9;
    width: fit-content;
}

.room-selector {
    margin-bottom: 20px;
}

.room-selector select {
    background: #efc74a;
    border: none;
    border-radius: 8px;
    padding: 8px 12px;
    font-weight: 600;
    cursor: pointer;
}

.booking-card {
    background: #cfe2ff;
    border-radius: 10px;
    padding: 28px;
    max-width: 700px;
    margin: 0; /* align left with heading */
}

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 18px;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 6px;
    color: #2c2c2c;
}

input,
select,
textarea {
    width: 100%;
    padding: 11px 14px;
    border: none;
    border-radius: 8px;
    background: white;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group.warning input,
.form-group.warning select,
.room-selector.warning select,
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

textarea {
    resize: vertical;
}

.button-wrapper {
    display: flex;
    justify-content: flex-end;
}

button {
    background: #1f3768;
    color: white;
    border: none;
    border-radius: 7px;
    padding: 10px 28px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.2s;
}

button:hover {
    background: #2a4b90;
}

@media (max-width: 768px) {
    .booking-page {
        padding: 15px;
    }

    .booking-card {
        padding: 20px;
    }
}

.banner-preview {
    margin-top: 15px;
}

.banner-preview img {
    width: 100%;
    max-width: 350px;
    border-radius: 10px;
    border: 1px solid #ddd;
    object-fit: cover;
}

.error {
    margin-top: 5px;
    color: #dc3545;
    font-size: 13px;
}

.dialog-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);

    display: flex;
    justify-content: center;
    align-items: center;

    z-index: 9999;
}

.dialog {
    background: white;
    width: 420px;
    max-width: 90%;
    border-radius: 12px;
    padding: 30px;
    text-align: center;

    animation: popup 0.2s ease;
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

.dialog h3 {
    margin-bottom: 10px;
    color: #173b7a;
}

.dialog p {
    color: #555;
    margin-bottom: 25px;
    line-height: 1.5;
}

.dialog button {
    min-width: 120px;
}

@keyframes popup {
    from {
        transform: scale(0.9);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
