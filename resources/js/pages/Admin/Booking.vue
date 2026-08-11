<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

type BookingStatus =
    'pending' | 'approved' | 'rejected' | 'cancelled' | 'finished';

interface Booking {
    id: number;
    code: string;
    room: string;
    borrower: string;
    activity: string;
    date: string;
    start: string;
    end: string;
    status: BookingStatus;
}

interface Room {
    id: number;
    name: string;
}

interface Stats {
    total: number;
    pending: number;
    approved: number;
    finished: number;
}

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    bookings: Pagination<Booking>;
    rooms?: Room[];
    stats?: Stats;
    filters?: {
        search?: string;
        status?: string;
        room?: string;
    };
}>();

const search = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');
const roomFilter = ref(props.filters?.room ?? '');
const today = new Date().toISOString().split('T')[0];

const showAddBookingModal = ref(false);
const showAddBookingSuccess = ref(false);
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedBookingId = ref<number | null>(null);
const selectedBookingCode = ref<string | null>(null);
const rejectReason = ref('');
const rejectValidationErrors = ref({ reason: '' });
const addBookingPreview = ref<string | null>(null);
const addBookingSubmitted = ref(false);

const addBookingForm = useForm({
    room_id: null as number | null,
    date: '',
    start_time: '',
    end_time: '',
    title: '',
    participants: null as number | null,
    request: '',
    status: 'pending' as 'pending' | 'approved',
    banner: null as File | null,
});

const addBookingHasWarning = (field: string): boolean => {
    if (!addBookingSubmitted.value) {
        return false;
    }

    switch (field) {
        case 'room_id':
            return addBookingForm.room_id === null;
        case 'date':
            return !addBookingForm.date;
        case 'start_time':
        case 'end_time':
        case 'title':
            return !addBookingForm[field];
        case 'participants':
            return !addBookingForm.participants || addBookingForm.participants < 1;
        default:
            return false;
    }
};

const addBookingMinStartTime = computed(() => {
    if (addBookingForm.date !== today) {
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

const addBookingMinEndTime = computed(() => {
    return addBookingForm.start_time || addBookingMinStartTime.value;
});

function closeAddBookingModal() {
    showAddBookingModal.value = false;
    showAddBookingSuccess.value = false;
    addBookingForm.reset();
    addBookingPreview.value = null;
    addBookingSubmitted.value = false;
}

function openApproveModal(id: number, code: string) {
    selectedBookingId.value = id;
    selectedBookingCode.value = code;
    showApproveModal.value = true;
}

function closeApproveModal() {
    showApproveModal.value = false;
    selectedBookingId.value = null;
    selectedBookingCode.value = null;
}

function openRejectModal(id: number, code: string) {
    selectedBookingId.value = id;
    selectedBookingCode.value = code;
    rejectReason.value = '';
    rejectValidationErrors.value.reason = '';
    showRejectModal.value = true;
}

function closeRejectModal() {
    showRejectModal.value = false;
    selectedBookingId.value = null;
    selectedBookingCode.value = null;
    rejectReason.value = '';
    rejectValidationErrors.value.reason = '';
}

function handleAddBookingFile(event: Event) {
    const target = event.target as HTMLInputElement;

    if (!target.files?.length) {
        return;
    }

    const file = target.files[0];
    addBookingForm.banner = file;
    addBookingPreview.value = URL.createObjectURL(file);
}

function submitAddBooking() {
    addBookingSubmitted.value = true;

    if (
        addBookingHasWarning('room_id') ||
        addBookingHasWarning('date') ||
        addBookingHasWarning('start_time') ||
        addBookingHasWarning('end_time') ||
        addBookingHasWarning('title') ||
        addBookingHasWarning('participants')
    ) {
        return;
    }

    addBookingForm.post('/admin/bookings', {
        forceFormData: true,
        onSuccess: () => {
            showAddBookingSuccess.value = true;
            addBookingForm.reset();
            addBookingPreview.value = null;
            addBookingSubmitted.value = false;
        },
    });
}

function fetchBookings() {
    router.get(
        '/admin/bookings',
        {
            search: search.value,
            status: statusFilter.value,
            room: roomFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

watchDebounced(search, fetchBookings, {
    debounce: 500,
});

watch([statusFilter, roomFilter], fetchBookings);

function badgeClass(status: BookingStatus) {
    return {
        pending: 'bg-yellow-100 text-yellow-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
        cancelled: 'bg-gray-200 text-gray-700',
        finished: 'bg-blue-100 text-blue-700',
    }[status];
}

function confirmApprove() {
    if (!selectedBookingId.value) {
        return;
    }

    router.patch(`/admin/bookings/${selectedBookingId.value}/approve`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            closeApproveModal();
        },
    });
}

function confirmReject() {
    rejectValidationErrors.value.reason = '';

    if (!rejectReason.value.trim()) {
        rejectValidationErrors.value.reason = 'Alasan penolakan wajib diisi.';

        return;
    }

    if (!selectedBookingId.value) {
        return;
    }

    router.patch(
        `/admin/bookings/${selectedBookingId.value}/reject`,
        { reason: rejectReason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeRejectModal();
            },
        },
    );
}

function approve(id: number, code: string) {
    openApproveModal(id, code);
}

function reject(id: number, code: string) {
    openRejectModal(id, code);
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl font-bold">Booking</h1>

                <p class="text-gray-500">Kelola seluruh peminjaman ruangan.</p>
            </div>

            <button
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                @click="showAddBookingModal = true"
            >
                Tambah Peminjaman
            </button>
        </div>

        <Teleport to="body">
            <div
                v-if="showAddBookingModal"
                class="add-booking-modal-overlay"
                @click.self="closeAddBookingModal"
            >
                <div class="add-booking-modal">
                    <div class="modal-header">
                        <div>
                            <h2 class="text-xl font-semibold">Tambah Peminjaman</h2>
                            <p class="text-sm text-gray-500">
                                Ajukan peminjaman baru langsung dari panel admin.
                            </p>
                        </div>

                        <button
                            class="modal-close"
                            type="button"
                            @click="closeAddBookingModal"
                            aria-label="Tutup"
                        >
                            ×
                        </button>
                    </div>

                    <div v-if="showAddBookingSuccess" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                        Peminjaman berhasil ditambahkan.
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select
                                v-model="addBookingForm.status"
                                class="mt-2 w-full rounded-lg border px-4 py-2"
                            >
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ruangan</label>
                            <select
                                v-model.number="addBookingForm.room_id"
                                class="mt-2 w-full rounded-lg border px-4 py-2"
                            >
                                <option :value="null" disabled hidden>Pilih Ruangan</option>
                                <option
                                    v-for="room in rooms"
                                    :key="room.id"
                                    :value="room.id"
                                >
                                    {{ room.name }}
                                </option>
                            </select>
                            <p v-if="addBookingHasWarning('room_id')" class="mt-2 text-sm text-red-600">
                                Ruangan wajib dipilih.
                            </p>
                            <p v-if="addBookingForm.errors.room_id" class="mt-2 text-sm text-red-600">
                                {{ addBookingForm.errors.room_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input
                                type="date"
                                v-model="addBookingForm.date"
                                class="mt-2 w-full rounded-lg border px-4 py-2"
                            />
                            <p v-if="addBookingHasWarning('date')" class="mt-2 text-sm text-red-600">
                                Tanggal wajib diisi.
                            </p>
                            <p v-if="addBookingForm.errors.date" class="mt-2 text-sm text-red-600">
                                {{ addBookingForm.errors.date }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Dari Jam</label>
                            <input
                                type="time"
                                v-model="addBookingForm.start_time"
                                :min="addBookingMinStartTime"
                                max="23:59"
                                class="mt-2 w-full rounded-lg border px-4 py-2"
                            />
                            <p v-if="addBookingHasWarning('start_time')" class="mt-2 text-sm text-red-600">
                                Waktu mulai wajib diisi.
                            </p>
                            <p v-if="addBookingForm.errors.start_time" class="mt-2 text-sm text-red-600">
                                {{ addBookingForm.errors.start_time }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sampai Jam</label>
                            <input
                                type="time"
                                v-model="addBookingForm.end_time"
                                :min="addBookingMinEndTime"
                                max="23:59"
                                step="1800"
                                class="mt-2 w-full rounded-lg border px-4 py-2"
                            />
                            <p v-if="addBookingHasWarning('end_time')" class="mt-2 text-sm text-red-600">
                                Waktu selesai wajib diisi.
                            </p>
                            <p v-if="addBookingForm.errors.end_time" class="mt-2 text-sm text-red-600">
                                {{ addBookingForm.errors.end_time }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Judul Kegiatan</label>
                            <input
                                type="text"
                                v-model="addBookingForm.title"
                                class="mt-2 w-full rounded-lg border px-4 py-2"
                            />
                            <p v-if="addBookingHasWarning('title')" class="mt-2 text-sm text-red-600">
                                Judul wajib diisi.
                            </p>
                            <p v-if="addBookingForm.errors.title" class="mt-2 text-sm text-red-600">
                                {{ addBookingForm.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jumlah Orang</label>
                            <input
                                type="number"
                                min="1"
                                v-model.number="addBookingForm.participants"
                                class="mt-2 w-full rounded-lg border px-4 py-2"
                            />
                            <p v-if="addBookingHasWarning('participants')" class="mt-2 text-sm text-red-600">
                                Jumlah orang wajib diisi dan minimal 1.
                            </p>
                            <p v-if="addBookingForm.errors.participants" class="mt-2 text-sm text-red-600">
                                {{ addBookingForm.errors.participants }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Permintaan Khusus</label>
                            <textarea
                                rows="3"
                                v-model="addBookingForm.request"
                                class="mt-2 w-full rounded-lg border px-4 py-2"
                            ></textarea>
                            <p v-if="addBookingForm.errors.request" class="mt-2 text-sm text-red-600">
                                {{ addBookingForm.errors.request }}
                            </p>
                        </div>

                        <div class="md:col-span-2" v-if="addBookingForm.room_id === 1">
                            <label class="block text-sm font-medium text-gray-700">Unggah Banner Rapat</label>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png"
                                @change="handleAddBookingFile"
                                class="mt-2 w-full"
                            />
                            <small class="text-sm text-gray-500">
                                Format yang didukung: JPG, JPEG, PNG
                            </small>
                            <div v-if="addBookingPreview" class="banner-preview mt-3">
                                <img :src="addBookingPreview" alt="Preview Banner" />
                            </div>
                            <p v-if="addBookingForm.errors.banner" class="mt-2 text-sm text-red-600">
                                {{ addBookingForm.errors.banner }}
                            </p>
                        </div>

                        <div class="md:col-span-2 flex justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100"
                                @click="closeAddBookingModal"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                :disabled="addBookingForm.processing"
                                @click="submitAddBooking"
                            >
                                {{ addBookingForm.processing ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="showApproveModal"
                class="add-booking-modal-overlay"
                @click.self="closeApproveModal"
            >
                <div class="add-booking-modal">
                    <div class="modal-header">
                        <div>
                            <h2 class="text-xl font-semibold">Setujui Booking</h2>
                            <p class="text-sm text-gray-500">
                                Yakin ingin menyetujui booking <strong>{{ selectedBookingCode }}</strong>?
                            </p>
                        </div>

                        <button
                            class="modal-close"
                            type="button"
                            @click="closeApproveModal"
                            aria-label="Tutup"
                        >
                            ×
                        </button>
                    </div>

                    <div class="space-y-4">
                        <p class="text-sm text-gray-700">
                            Booking akan langsung ditandai sebagai <strong>Approved</strong> dan tercatat sebagai diproses.
                        </p>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100"
                                @click="closeApproveModal"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                                @click="confirmApprove"
                            >
                                Setujui
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="showRejectModal"
                class="add-booking-modal-overlay"
                @click.self="closeRejectModal"
            >
                <div class="add-booking-modal">
                    <div class="modal-header">
                        <div>
                            <h2 class="text-xl font-semibold">Tolak Booking</h2>
                            <p class="text-sm text-gray-500">
                                Masukkan alasan penolakan untuk booking <strong>{{ selectedBookingCode }}</strong>.
                            </p>
                        </div>

                        <button
                            class="modal-close"
                            type="button"
                            @click="closeRejectModal"
                            aria-label="Tutup"
                        >
                            ×
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Alasan Penolakan</label>
                            <textarea
                                v-model="rejectReason"
                                rows="4"
                                class="mt-2 w-full rounded-lg border px-4 py-2"
                            ></textarea>
                            <p v-if="rejectValidationErrors.reason" class="mt-2 text-sm text-red-600">
                                {{ rejectValidationErrors.reason }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100"
                                @click="closeRejectModal"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                                @click="confirmReject"
                            >
                                Tolak
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Total</p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ stats?.total }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Pending</p>

                <h2 class="mt-2 text-3xl font-bold text-yellow-600">
                    {{ stats?.pending }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Approved</p>

                <h2 class="mt-2 text-3xl font-bold text-green-600">
                    {{ stats?.approved }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Selesai</p>

                <h2 class="mt-2 text-3xl font-bold text-blue-600">
                    {{ stats?.finished }}
                </h2>
            </div>
        </div>

        <!-- Filter -->

        <div class="rounded-xl bg-white p-5 shadow">
            <div class="grid gap-4 md:grid-cols-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari booking..."
                    class="rounded-lg border px-4 py-2 outline-none focus:border-blue-500"
                />

                <select
                    v-model="statusFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="finished">Finished</option>
                </select>

                <select
                    v-model="roomFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Ruangan</option>
                    <option
                        v-for="room in rooms"
                        :key="room.id"
                        :value="room.id"
                    >
                        {{ room.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Table -->

        <div class="overflow-hidden rounded-xl bg-white shadow">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr class="text-left text-sm">
                        <th class="px-5 py-4">Kode</th>
                        <th class="px-5 py-4">Peminjam</th>
                        <th class="px-5 py-4">Ruangan</th>
                        <th class="px-5 py-4">Kegiatan</th>
                        <th class="px-5 py-4">Jadwal</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="booking in bookings?.data"
                        :key="booking.id"
                        class="border-t hover:bg-gray-50"
                    >
                        <td class="px-5 py-4 font-medium">
                            {{ booking.code }}
                        </td>

                        <td class="px-5 py-4">
                            {{ booking.borrower }}
                        </td>

                        <td class="px-5 py-4">
                            {{ booking.room }}
                        </td>

                        <td class="px-5 py-4">
                            {{ booking.activity }}
                        </td>

                        <td class="px-5 py-4">
                            {{ booking.date }}

                            <div class="text-xs text-gray-500">
                                {{ booking.start }} - {{ booking.end }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <span
                                :class="badgeClass(booking.status)"
                                class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                            >
                                {{ booking.status }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <button
                                    class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-100"
                                >
                                    Detail
                                </button>

                                <button
                                    v-if="booking.status === 'pending'"
                                    class="rounded-lg bg-green-600 px-3 py-2 text-sm text-white hover:bg-green-700"
                                    @click="approve(booking.id, booking.code)"
                                >
                                    Approve
                                </button>

                                <button
                                    v-if="booking.status === 'pending'"
                                    class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                                    @click="reject(booking.id, booking.code)"
                                >
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->

        <div class="flex justify-end gap-2">
            <button
                class="rounded-lg border px-4 py-2 hover:bg-gray-100"
                :disabled="bookings?.current_page === 1"
                @click="
                    router.get('/admin/bookings', {
                        page: bookings.current_page - 1,
                        search,
                        status: statusFilter,
                        room: roomFilter,
                    })
                "
            >
                Previous
            </button>

            <button
                class="rounded-lg border px-4 py-2 hover:bg-gray-100"
                :disabled="bookings.current_page === bookings.last_page"
                @click="
                    router.get('/admin/bookings', {
                        page: bookings.current_page + 1,
                        search,
                        status: statusFilter,
                        room: roomFilter,
                    })
                "
            >
                Next
            </button>
        </div>
    </div>
</template>

<style scoped>
.add-booking-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
    padding: 20px;
}

.add-booking-modal {
    width: min(100%, 960px);
    max-height: min(100%, 92vh);
    overflow-y: auto;
    background: #ffffff;
    border-radius: 24px;
    box-shadow: 0 24px 80px rgba(15, 23, 42, 0.18);
    padding: 28px;
    animation: modalPop 0.18s ease-out;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 20px;
}

.modal-close {
    background: transparent;
    border: none;
    color: #334155;
    font-size: 28px;
    line-height: 1;
    cursor: pointer;
    padding: 0;
}

.modal-close:hover {
    color: #0f172a;
}

.banner-preview img {
    width: 100%;
    max-width: 350px;
    border-radius: 10px;
    border: 1px solid #ddd;
    object-fit: cover;
}

@keyframes modalPop {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
