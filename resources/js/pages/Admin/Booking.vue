<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import AppInput from '@/components/atoms/AppInput.vue';
import AppSelect from '@/components/atoms/AppSelect.vue';
import AppTextarea from '@/components/atoms/AppTextarea.vue';
import FormField from '@/components/molecules/FormField.vue';
import StatCard from '@/components/molecules/StatCard.vue';
import AppModal from '@/components/organisms/AppModal.vue';
import ConfirmModal from '@/components/organisms/ConfirmModal.vue';
import DetailModal from '@/components/organisms/DetailModal.vue';
import { useModalManager } from '@/composables/useModal';
import type {
    AdminBooking as Booking,
    BookingStatus,
    BookingStats as Stats,
    Pagination,
    RoomOption as Room,
} from '@/types/admin';
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

const showAddBookingModal = computed(() => isModalOpen('add'));
const showAddBookingSuccess = ref(false);
const { openModal, closeModal, isModalOpen } = useModalManager<
    'add' | 'approve' | 'reject' | 'finish' | 'detail'
>();
const showApproveModal = computed(() => isModalOpen('approve'));
const showRejectModal = computed(() => isModalOpen('reject'));
const showFinishModal = computed(() => isModalOpen('finish'));
const showDetailModal = computed(() => isModalOpen('detail'));
const selectedBookingId = ref<number | null>(null);
const selectedBookingCode = ref<string | null>(null);
const detailBooking = ref<Booking | null>(null);
const bookingToDelete = ref<Booking | null>(null);
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
            return (
                !addBookingForm.participants || addBookingForm.participants < 1
            );
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
    closeModal();
    showAddBookingSuccess.value = false;
    addBookingForm.reset();
    addBookingPreview.value = null;
    addBookingSubmitted.value = false;
}

function openApproveModal(id: number, code: string) {
    selectedBookingId.value = id;
    selectedBookingCode.value = code;
    openModal('approve');
}

function closeApproveModal() {
    closeModal();
    selectedBookingId.value = null;
    selectedBookingCode.value = null;
}

function openRejectModal(id: number, code: string) {
    selectedBookingId.value = id;
    selectedBookingCode.value = code;
    rejectReason.value = '';
    rejectValidationErrors.value.reason = '';
    openModal('reject');
}

function closeRejectModal() {
    closeModal();
    selectedBookingId.value = null;
    selectedBookingCode.value = null;
    rejectReason.value = '';
    rejectValidationErrors.value.reason = '';
}

function openFinishModal(id: number, code: string) {
    selectedBookingId.value = id;
    selectedBookingCode.value = code;
    openModal('finish');
}

function closeFinishModal() {
    closeModal();
    selectedBookingId.value = null;
    selectedBookingCode.value = null;
}

function openDetailModal(booking: Booking) {
    detailBooking.value = booking;
    openModal('detail');
}

function closeDetailModal() {
    closeModal();
    detailBooking.value = null;
}

function openDeleteModal(booking: Booking) {
    bookingToDelete.value = booking;
}
function closeDeleteModal() {
    bookingToDelete.value = null;
}
function deleteBooking() {
    if (!bookingToDelete.value) {
        return;
    }

    router.delete(`/admin/bookings/${bookingToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
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

    router.patch(
        `/admin/bookings/${selectedBookingId.value}/approve`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeApproveModal();
            },
        },
    );
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

function confirmFinish() {
    if (!selectedBookingId.value) {
        return;
    }

    router.patch(
        '/admin/bookings/' + selectedBookingId.value + '/finish',
        {},
        {
            preserveScroll: true,
            onSuccess: () => closeFinishModal(),
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
    <div class="w-full max-w-7xl space-y-6">
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div>
                <h1 class="text-3xl font-bold">Booking</h1>

                <p class="text-gray-500">Kelola seluruh peminjaman ruangan.</p>
            </div>

            <button
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                @click="openModal('add')"
            >
                Tambah Peminjaman
            </button>
        </div>

        <AppModal
            v-if="showAddBookingModal"
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
                <FormField label="Status" appearance="admin">
                    <AppSelect v-model="addBookingForm.status">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
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
                        <option :value="null" disabled hidden>
                            Pilih Ruangan
                        </option>
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
                        (addBookingHasWarning('date')
                            ? 'Tanggal wajib diisi.'
                            : '')
                    "
                >
                    <AppInput
                        v-model="addBookingForm.date"
                        appearance="admin"
                        type="date"
                    />
                </FormField>
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
                    <AppInput
                        v-model="addBookingForm.start_time"
                        appearance="admin"
                        type="time"
                        :min="addBookingMinStartTime"
                        max="23:59"
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
                    <AppInput
                        v-model="addBookingForm.end_time"
                        appearance="admin"
                        type="time"
                        :min="addBookingMinEndTime"
                        max="23:59"
                        step="1800"
                    />
                </FormField>
                <FormField
                    label="Judul Kegiatan"
                    appearance="admin"
                    required
                    :error="
                        addBookingForm.errors.title ||
                        (addBookingHasWarning('title')
                            ? 'Judul wajib diisi.'
                            : '')
                    "
                >
                    <AppInput
                        v-model="addBookingForm.title"
                        appearance="admin"
                    />
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
                <FormField
                    v-if="addBookingForm.room_id === 1"
                    class="md:col-span-2"
                    label="Unggah Banner Rapat"
                    appearance="admin"
                    hint="Format yang didukung: JPG, JPEG, PNG"
                    :error="addBookingForm.errors.banner"
                >
                    <input
                        type="file"
                        accept=".jpg,.jpeg,.png"
                        class="w-full"
                        @change="handleAddBookingFile"
                    />
                    <div v-if="addBookingPreview" class="banner-preview mt-3">
                        <img :src="addBookingPreview" alt="Preview Banner" />
                    </div>
                </FormField>
                <div class="flex justify-end gap-3 md:col-span-2">
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
                        {{
                            addBookingForm.processing
                                ? 'Menyimpan...'
                                : 'Simpan'
                        }}
                    </button>
                </div>
            </div>
        </AppModal>

        <DetailModal
            v-if="showDetailModal"
            title="Detail Booking"
            max-width="3xl"
            @close="closeDetailModal"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-500">Kode Booking</p>
                    <p class="mt-1 font-medium">{{ detailBooking?.code }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="mt-1 font-medium capitalize">
                        {{ detailBooking?.status }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Peminjam</p>
                    <p class="mt-1 font-medium">
                        {{ detailBooking?.borrower }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ruangan</p>
                    <p class="mt-1 font-medium">{{ detailBooking?.room }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-500">Kegiatan</p>
                    <p class="mt-1 font-medium">
                        {{ detailBooking?.activity }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Tanggal</p>
                    <p class="mt-1 font-medium">{{ detailBooking?.date }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Waktu</p>
                    <p class="mt-1 font-medium">
                        {{ detailBooking?.start }} - {{ detailBooking?.end }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Catatan/Request</p>
                    <p class="mt-1 font-medium">
                        {{ detailBooking?.request || '-' }}
                    </p>
                </div>
                <div v-if="detailBooking?.processed_notes">
                    <p class="text-sm text-gray-500">Processed Note</p>
                    <p class="mt-1 font-medium">
                        {{ detailBooking.processed_notes }}
                    </p>
                </div>
            </div>
        </DetailModal>

        <AppModal
            v-if="showApproveModal"
            title="Setujui Booking"
            max-width="lg"
            @close="closeApproveModal"
        >
            <div class="space-y-4">
                <p class="text-sm text-gray-700">
                    Booking <strong>{{ selectedBookingCode }}</strong> akan
                    langsung ditandai sebagai <strong>Approved</strong> dan
                    tercatat sebagai diproses.
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
        </AppModal>

        <AppModal
            v-if="showRejectModal"
            title="Tolak Booking"
            max-width="lg"
            @close="closeRejectModal"
        >
            <div class="space-y-4">
                <FormField
                    label="Alasan Penolakan"
                    appearance="admin"
                    required
                    :error="rejectValidationErrors.reason"
                    ><AppTextarea v-model="rejectReason" rows="4"
                /></FormField>

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
        </AppModal>

        <AppModal
            v-if="showFinishModal"
            title="Akhiri Peminjaman"
            max-width="lg"
            @close="closeFinishModal"
        >
            <div class="space-y-4">
                <p class="text-sm text-gray-700">
                    Peminjaman <strong>{{ selectedBookingCode }}</strong> akan
                    langsung ditandai sebagai <strong>Finished</strong>.
                </p>
                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100"
                        @click="closeFinishModal"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        @click="confirmFinish"
                    >
                        Akhiri Sekarang
                    </button>
                </div>
            </div>
        </AppModal>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <StatCard label="Total" :value="stats?.total" />
            <StatCard label="Pending" :value="stats?.pending" tone="warning" />
            <StatCard
                label="Approved"
                :value="stats?.approved"
                tone="success"
            />
            <StatCard label="Finished" :value="stats?.finished" />
        </div>
        <!-- Filter -->

        <div class="rounded-xl bg-white p-5 shadow">
            <div class="grid gap-4 md:grid-cols-3">
                <AppInput
                    v-model="search"
                    appearance="admin"
                    type="text"
                    placeholder="Cari booking..."
                    class="rounded-lg border px-4 py-2 outline-none focus:border-blue-500"
                />

                <AppSelect
                    v-model="statusFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="finished">Finished</option>
                </AppSelect>

                <AppSelect
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
                </AppSelect>
            </div>
        </div>

        <!-- Table -->

        <div class="overflow-x-auto rounded-xl bg-white shadow">
            <table class="w-full min-w-[1050px]">
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
                                    @click="openDetailModal(booking)"
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
                                    v-if="booking.status === 'approved'"
                                    class="rounded-lg bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700"
                                    @click="
                                        openFinishModal(
                                            booking.id,
                                            booking.code,
                                        )
                                    "
                                >
                                    Akhiri Sekarang
                                </button>

                                <button
                                    v-if="booking.status === 'pending'"
                                    class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                                    @click="reject(booking.id, booking.code)"
                                >
                                    Reject
                                </button>
                                <button
                                    class="rounded-lg bg-red-700 px-3 py-2 text-sm text-white hover:bg-red-800"
                                    @click="openDeleteModal(booking)"
                                >
                                    Hapus
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
        <ConfirmModal
            v-if="bookingToDelete"
            title="Hapus Booking"
            :message="`Booking ${bookingToDelete.code} untuk ${bookingToDelete.room} akan dihapus. Jadwalnya akan tersedia kembali untuk dipinjam.`"
            confirm-label="Hapus"
            @close="closeDeleteModal"
            @confirm="deleteBooking"
        />
    </div>
</template>

<style scoped>
.banner-preview img {
    width: 100%;
    max-width: 350px;
    border-radius: 10px;
    border: 1px solid #ddd;
    object-fit: cover;
}
</style>
