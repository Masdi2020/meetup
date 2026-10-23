<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    ActionIconButton,
    AppButton,
    AppSelect,
    AppTextarea,
    BookingStatusBadge,
} from '@/components/atoms';
import { FormField, PageHeader, StatCard } from '@/components/molecules';
import {
    AdminBookingCreateModal,
    AdminBookingExportModal,
    AdminSearchPanel,
    AppModal,
    BookingEditModal,
    BookingResults,
    ConfirmModal,
    DetailModal,
} from '@/components/organisms';
import { useAdminFilters } from '@/composables/admin/useAdminFilters';
import { useBookingPageUpdates } from '@/composables/useBookingUpdates';
import { useModalManager } from '@/composables/useModal';
import type { BookingExportOptions } from '@/lib/bookingExport';
import {
    index,
    update,
    cancel,
    approve as approveBooking,
    reject as rejectBooking,
    finish,
    destroy,
} from '@/routes/admin/bookings';
import {
    documentation,
    meetingMinutes,
    destroy as destroyResult,
    show as showResult,
} from '@/routes/admin/bookings/results';
import type {
    AdminBookingStatusOption,
    AdminBooking as Booking,
    BookingStats as Stats,
    BorrowerOption,
    Pagination,
    BookingRoomOption as Room,
} from '@/types/admin';
import type { BookingEdit } from '@/types/booking';

const props = defineProps<{
    canDeleteBookings: boolean;
    exportOptions: BookingExportOptions;
    bookings: Pagination<Booking>;
    rooms?: Room[];
    users?: BorrowerOption[];
    statuses?: AdminBookingStatusOption[];
    stats?: Stats;
    filters?: {
        search?: string;
        status?: string;
        room?: string;
    };
}>();

const search = ref(props.filters?.search ?? '');

useBookingPageUpdates(['bookings', 'stats']);
const statusFilter = ref(props.filters?.status ?? '');
const roomFilter = ref(props.filters?.room ?? '');
const { applyFilters, resetFilters, goToPage } = useAdminFilters(index.url(), {
    debouncedSources: [search],
    instantSources: [statusFilter, roomFilter],
    query: () => ({
        search: search.value,
        status: statusFilter.value,
        room: roomFilter.value,
    }),
    reset: () => {
        search.value = '';
        statusFilter.value = '';
        roomFilter.value = '';
    },
});

const showAddBookingModal = computed(() => isModalOpen('add'));
const { openModal, closeModal, isModalOpen } = useModalManager<
    'add' | 'edit' | 'approve' | 'reject' | 'finish' | 'detail' | 'export'
>();
const showEditBookingModal = computed(() => isModalOpen('edit'));
const showApproveModal = computed(() => isModalOpen('approve'));
const showRejectModal = computed(() => isModalOpen('reject'));
const showFinishModal = computed(() => isModalOpen('finish'));
const showDetailModal = computed(() => isModalOpen('detail'));
const showExportModal = computed(() => isModalOpen('export'));
const selectedBookingId = ref<number | null>(null);
const detailBooking = ref<Booking | null>(null);
const bookingToDelete = ref<Booking | null>(null);
const bookingToCancel = ref<Booking | null>(null);
const isCancellingBooking = ref(false);
const rejectReason = ref('');
const rejectValidationErrors = ref({ reason: '' });
const resultsBusy = ref(false);
const editingBooking = ref<BookingEdit | null>(null);

function openEditBookingModal(booking: Booking) {
    editingBooking.value = {
        id: booking.id,
        room_id: booking.room_id,
        title: booking.activity,
        date: booking.date,
        start_time: booking.start,
        end_time: booking.end,
    };
    openModal('edit');
}

function closeEditBookingModal() {
    closeModal();
    editingBooking.value = null;
}

function openCancelBookingModal(booking: Booking) {
    bookingToCancel.value = booking;
}

function closeCancelBookingModal() {
    if (!isCancellingBooking.value) {
        bookingToCancel.value = null;
    }
}

function cancelApprovedBooking() {
    if (!bookingToCancel.value) {
        return;
    }

    isCancellingBooking.value = true;
    router.put(
        cancel.url(bookingToCancel.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isCancellingBooking.value = false;
                bookingToCancel.value = null;
            },
        },
    );
}

function openApproveModal(id: number) {
    selectedBookingId.value = id;
    openModal('approve');
}

function closeApproveModal() {
    closeModal();
    selectedBookingId.value = null;
}

function openRejectModal(id: number) {
    selectedBookingId.value = id;
    rejectReason.value = '';
    rejectValidationErrors.value.reason = '';
    openModal('reject');
}

function closeRejectModal() {
    closeModal();
    selectedBookingId.value = null;
    rejectReason.value = '';
    rejectValidationErrors.value.reason = '';
}

function openFinishModal(id: number) {
    selectedBookingId.value = id;
    openModal('finish');
}

function closeFinishModal() {
    closeModal();
    selectedBookingId.value = null;
}

watch(
    () => props.bookings.data,
    (bookings) => {
        if (detailBooking.value) {
            detailBooking.value =
                bookings.find(
                    (booking) => booking.id === detailBooking.value?.id,
                ) ?? null;
        }
    },
);

function openDetailModal(booking: Booking) {
    detailBooking.value = booking;
    openModal('detail');
}

function closeDetailModal() {
    if (resultsBusy.value) {
        return;
    }

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

    router.delete(destroy.url(bookingToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
}
function confirmApprove() {
    if (!selectedBookingId.value) {
        return;
    }

    router.patch(
        approveBooking.url(selectedBookingId.value),
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
        rejectBooking.url(selectedBookingId.value),
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
        finish.url(selectedBookingId.value),
        {},
        {
            preserveScroll: true,
            onSuccess: () => closeFinishModal(),
        },
    );
}

function approve(id: number) {
    openApproveModal(id);
}

function reject(id: number) {
    openRejectModal(id);
}
</script>

<template>
    <div class="app-page">
        <PageHeader
            title="Booking"
            description="Kelola seluruh peminjaman ruangan."
        >
            <div class="flex flex-wrap gap-3">
                <AppButton
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="openModal('export')"
                >
                    Export Data
                </AppButton>
                <AppButton
                    variant="primary"
                    size="sm"
                    @click="openModal('add')"
                >
                    Tambah Peminjaman
                </AppButton>
            </div>
        </PageHeader>

        <AdminBookingExportModal
            :open="showExportModal"
            :search="search"
            :room="roomFilter"
            :status="statusFilter"
            :options="exportOptions"
            @close="closeModal"
        />
        <AdminBookingCreateModal
            :open="showAddBookingModal"
            :rooms="rooms"
            :users="users"
            :statuses="statuses"
            @close="closeModal"
        />

        <BookingEditModal
            v-if="showEditBookingModal && editingBooking"
            :key="editingBooking.id"
            :booking="editingBooking"
            :update-url="update.url"
            @close="closeEditBookingModal"
        />

        <DetailModal
            v-if="showDetailModal"
            title="Detail Booking"
            max-width="3xl"
            @close="closeDetailModal"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-500">ID Booking</p>
                    <p class="mt-1 font-medium">{{ detailBooking?.id }}</p>
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
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-500">Hasil Rapat</p>
                    <BookingResults
                        v-if="detailBooking"
                        :key="detailBooking.id"
                        class="mt-3"
                        :booking-id="detailBooking.id"
                        :routes="{
                            documentation: documentation.url,
                            meetingMinutes: meetingMinutes.url,
                            destroy: destroyResult.url,
                            show: showResult.url,
                        }"
                        :documentations="detailBooking.documentations"
                        :meeting-minutes="detailBooking.meeting_minutes"
                        :editable="detailBooking.status === 'finished'"
                        @busy="resultsBusy = $event"
                    />
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
                    Booking ID <strong>{{ selectedBookingId }}</strong> akan
                    langsung ditandai sebagai <strong>Approved</strong> dan
                    tercatat sebagai diproses.
                </p>
                <div class="flex justify-end gap-3">
                    <AppButton
                        type="button"
                        variant="neutral"
                        size="sm"
                        @click="closeApproveModal"
                    >
                        Batal
                    </AppButton>
                    <AppButton
                        type="button"
                        variant="success"
                        size="sm"
                        @click="confirmApprove"
                    >
                        Setujui
                    </AppButton>
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
                    <AppButton
                        type="button"
                        variant="neutral"
                        size="sm"
                        @click="closeRejectModal"
                    >
                        Batal
                    </AppButton>
                    <AppButton
                        type="button"
                        variant="danger"
                        size="sm"
                        @click="confirmReject"
                    >
                        Tolak
                    </AppButton>
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
                    Peminjaman ID <strong>{{ selectedBookingId }}</strong> akan
                    langsung ditandai sebagai <strong>Finished</strong>.
                </p>
                <div class="flex justify-end gap-3">
                    <AppButton
                        type="button"
                        variant="neutral"
                        size="sm"
                        @click="closeFinishModal"
                    >
                        Batal
                    </AppButton>
                    <AppButton
                        type="button"
                        variant="primary"
                        size="sm"
                        @click="confirmFinish"
                    >
                        Akhiri Sekarang
                    </AppButton>
                </div>
            </div>
        </AppModal>

        <div class="stats-grid">
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

        <AdminSearchPanel
            v-model="search"
            placeholder="Cari booking..."
            :filter-columns="2"
            @search="applyFilters"
            @reset="resetFilters"
        >
            <template #filters>
                <AppSelect
                    v-model="statusFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Status</option>
                    <option
                        v-for="status in statuses"
                        :key="status.code"
                        :value="status.code"
                    >
                        {{ status.label }}
                    </option>
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
            </template>
        </AdminSearchPanel>

        <!-- Table -->

        <div class="ui-card overflow-x-auto">
            <table class="w-full min-w-[1050px]">
                <thead class="bg-gray-100">
                    <tr class="text-left text-sm">
                        <th class="px-5 py-4">ID</th>
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
                            {{ booking.id }}
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
                            <BookingStatusBadge :status="booking.status" />
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap justify-end gap-2">
                                <ActionIconButton
                                    action="detail"
                                    @click="openDetailModal(booking)"
                                />

                                <ActionIconButton
                                    v-if="booking.status === 'pending'"
                                    action="approve"
                                    @click="approve(booking.id)"
                                />

                                <ActionIconButton
                                    v-if="booking.status === 'approved'"
                                    action="edit"
                                    @click="openEditBookingModal(booking)"
                                />

                                <ActionIconButton
                                    v-if="booking.status === 'approved'"
                                    action="cancel"
                                    @click="openCancelBookingModal(booking)"
                                />

                                <ActionIconButton
                                    v-if="booking.status === 'approved'"
                                    action="finish"
                                    @click="openFinishModal(booking.id)"
                                />

                                <ActionIconButton
                                    v-if="booking.status === 'pending'"
                                    action="reject"
                                    @click="reject(booking.id)"
                                />
                                <ActionIconButton
                                    v-if="canDeleteBookings"
                                    action="delete"
                                    @click="openDeleteModal(booking)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->

        <div
            v-if="bookings.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-2 border-t p-4"
        >
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm transition disabled:cursor-not-allowed disabled:opacity-50"
                :class="bookings.current_page === 1 ? '' : 'hover:bg-gray-100'"
                :disabled="bookings?.current_page === 1"
                @click="goToPage(bookings.current_page - 1)"
            >
                Sebelumnya
            </button>

            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm transition disabled:cursor-not-allowed disabled:opacity-50"
                :class="
                    bookings.current_page === bookings.last_page
                        ? ''
                        : 'hover:bg-gray-100'
                "
                :disabled="bookings.current_page === bookings.last_page"
                @click="goToPage(bookings.current_page + 1)"
            >
                Berikutnya
            </button>
        </div>
        <ConfirmModal
            v-if="bookingToCancel"
            title="Batalkan Booking Approved"
            :message="`Booking ID ${bookingToCancel.id} milik ${bookingToCancel.borrower} akan dibatalkan.`"
            confirm-label="Batalkan Booking"
            :processing="isCancellingBooking"
            @close="closeCancelBookingModal"
            @confirm="cancelApprovedBooking"
        />
        <ConfirmModal
            v-if="bookingToDelete"
            title="Hapus Booking"
            :message="`Booking ID ${bookingToDelete.id} untuk ${bookingToDelete.room} akan dihapus. Jadwalnya akan tersedia kembali untuk dipinjam.`"
            confirm-label="Hapus"
            @close="closeDeleteModal"
            @confirm="deleteBooking"
        />
    </div>
</template>
