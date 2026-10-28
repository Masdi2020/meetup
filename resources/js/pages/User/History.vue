<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import { ref, watch } from 'vue';
import {
    ActionIconButton,
    AppButton,
    BookingStatusBadge,
} from '@/components/atoms';
import { PageHeader } from '@/components/molecules';
import {
    AppModal,
    BookingEditModal,
    BookingResults,
    ConfirmModal,
} from '@/components/organisms';
import { useAdminFilters } from '@/composables/admin/useAdminFilters';
import { useBookingPageUpdates } from '@/composables/useBookingUpdates';
import { update, cancel, finish } from '@/routes/booking';
import {
    documentation,
    meetingMinutes,
    destroy as destroyResult,
    show as showResult,
} from '@/routes/booking/results';
import { index as historyIndex } from '@/routes/history';
import type { Pagination } from '@/types/admin';
import type {
    BookingEdit,
    BookingHistory,
    BookingHistoryStatus,
    HistoryStatusOption,
} from '@/types/booking';

const bookingToCancel = ref<number | null>(null);
const isCancelling = ref(false);
const bookingToFinish = ref<number | null>(null);
const isFinishing = ref(false);
const resultsBusy = ref(false);
const selectedResultsBooking = ref<BookingHistory | null>(null);
const showResultsModal = ref(false);
const editingBooking = ref<BookingEdit | null>(null);
const props = defineProps<{
    histories: Pagination<BookingHistory> & { per_page: number };
    statuses: HistoryStatusOption[];
    filters: { status: BookingHistoryStatus | '' };
}>();
const filterStatus = ref(props.filters.status);
const { goToPage } = useAdminFilters(historyIndex.url(), {
    instantSources: [filterStatus],
    query: () => ({ status: filterStatus.value }),
    reset: () => {
        filterStatus.value = '';
    },
});

const openEditModal = (booking: BookingHistory) => {
    const [start, end] = booking.time.split(' - ');
    editingBooking.value = {
        id: booking.id,
        room_id: booking.room_id,
        title: booking.title,
        date: booking.date,
        start_time: start,
        end_time: end,
    };
};

const closeEditModal = () => {
    editingBooking.value = null;
};

const openResultsModal = (booking: BookingHistory) => {
    selectedResultsBooking.value = booking;
    showResultsModal.value = true;
};

const closeResultsModal = () => {
    if (resultsBusy.value) {
        return;
    }

    selectedResultsBooking.value = null;
    showResultsModal.value = false;
};

const resultStatus = (booking: BookingHistory) => {
    const hasDocumentation = booking.documentations.length > 0;
    const hasMinutes = booking.meeting_minutes !== null;

    if (hasDocumentation && hasMinutes) {
        return 'Lengkap';
    }

    if (hasDocumentation || hasMinutes) {
        return 'Sebagian';
    }

    return 'Belum dilengkapi';
};

useBookingPageUpdates(['histories']);

watch(
    () => props.histories.data,
    (bookings) => {
        if (selectedResultsBooking.value) {
            selectedResultsBooking.value =
                bookings.find(
                    (booking) =>
                        booking.id === selectedResultsBooking.value?.id,
                ) ?? null;
        }

        const canEdit = (id: number) =>
            bookings.some(
                (booking) =>
                    booking.id === id &&
                    ['Pending', 'Approved'].includes(booking.status),
            );

        if (editingBooking.value && !canEdit(editingBooking.value.id)) {
            closeEditModal();
        }

        if (bookingToCancel.value !== null && !canEdit(bookingToCancel.value)) {
            closeCancelModal();
        }

        if (
            bookingToFinish.value !== null &&
            !bookings.some(
                (booking) =>
                    booking.id === bookingToFinish.value &&
                    booking.status === 'Approved',
            )
        ) {
            closeFinishModal();
        }
    },
);

const openCancelModal = (id: number) => {
    bookingToCancel.value = id;
};

const closeCancelModal = () => {
    if (!isCancelling.value) {
        bookingToCancel.value = null;
    }
};

const cancelBooking = () => {
    if (bookingToCancel.value === null) {
        return;
    }

    isCancelling.value = true;
    router.put(
        cancel.url(bookingToCancel.value),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isCancelling.value = false;
                bookingToCancel.value = null;
            },
        },
    );
};

const closeFinishModal = () => {
    if (!isFinishing.value) {
        bookingToFinish.value = null;
    }
};

const finishBooking = () => {
    if (bookingToFinish.value === null) {
        return;
    }

    isFinishing.value = true;
    router.patch(
        finish.url(bookingToFinish.value),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isFinishing.value = false;
                bookingToFinish.value = null;
            },
        },
    );
};
</script>

<template>
    <div class="app-page history-page">
        <PageHeader title="Riwayat Peminjaman Ruang Rapat" />

        <div class="page-card">
            <div class="filter">
                <select
                    v-model="filterStatus"
                    class="ui-control"
                    aria-label="Filter status peminjaman"
                >
                    <option value="">Semua</option>
                    <option
                        v-for="status in statuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </select>
            </div>
            <div class="table-card ui-card">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Ruang Rapat</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Judul</th>
                            <th>Status</th>
                            <th>Hasil Rapat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(item, index) in histories.data"
                            :key="item.id"
                        >
                            <td>
                                {{
                                    (histories.current_page - 1) *
                                        histories.per_page +
                                    index +
                                    1
                                }}
                            </td>
                            <td>{{ item.room }}</td>
                            <td>{{ item.date }}</td>
                            <td>{{ item.time }}</td>
                            <td>{{ item.title }}</td>

                            <td>
                                <BookingStatusBadge
                                    :status="item.status"
                                    class="min-w-[85px]"
                                />
                            </td>

                            <td class="results-cell">
                                <span
                                    class="result-status"
                                    :class="
                                        resultStatus(item)
                                            .toLowerCase()
                                            .replaceAll(' ', '-')
                                    "
                                >
                                    {{ resultStatus(item) }}
                                </span>
                                <p
                                    v-if="item.results_available"
                                    class="result-summary"
                                >
                                    {{ item.documentations.length }} dokumentasi
                                    ·
                                    {{ item.meeting_minutes ? 1 : 0 }} notulensi
                                </p>
                            </td>

                            <td>
                                <div class="action-buttons">
                                    <button
                                        v-if="item.results_available"
                                        type="button"
                                        class="result-action-button"
                                        aria-label="Kelola hasil rapat"
                                        title="Kelola hasil rapat"
                                        @click="openResultsModal(item)"
                                    >
                                        <FileText
                                            :size="15"
                                            aria-hidden="true"
                                        />
                                    </button>
                                    <ActionIconButton
                                        v-if="
                                            item.status === 'Pending' ||
                                            item.status === 'Approved'
                                        "
                                        action="edit"
                                        @click="openEditModal(item)"
                                    />
                                    <ActionIconButton
                                        v-if="
                                            item.status === 'Pending' ||
                                            item.status === 'Approved'
                                        "
                                        action="cancel"
                                        @click="openCancelModal(item.id)"
                                    />
                                    <ActionIconButton
                                        v-if="item.status === 'Approved'"
                                        action="finish"
                                        @click="bookingToFinish = item.id"
                                    />
                                </div>
                            </td>
                        </tr>

                        <tr v-if="histories.data.length === 0">
                            <td colspan="8" class="empty">Tidak ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div
                v-if="histories.last_page > 1 || histories.current_page > 1"
                class="flex items-center justify-center gap-3"
            >
                <AppButton
                    variant="outline"
                    size="sm"
                    :disabled="histories.current_page <= 1"
                    @click="goToPage(histories.current_page - 1)"
                    >Sebelumnya</AppButton
                >
                <span class="text-sm text-gray-600"
                    >Halaman {{ histories.current_page }} dari
                    {{ histories.last_page }}</span
                >
                <AppButton
                    variant="outline"
                    size="sm"
                    :disabled="histories.current_page >= histories.last_page"
                    @click="goToPage(histories.current_page + 1)"
                    >Berikutnya</AppButton
                >
            </div>
        </div>

        <AppModal
            v-if="showResultsModal && selectedResultsBooking"
            title="Hasil Rapat"
            max-width="2xl"
            @close="closeResultsModal"
        >
            <div class="results-modal-content">
                <div class="booking-summary">
                    <div>
                        <span>Ruangan</span>
                        <strong>{{ selectedResultsBooking.room }}</strong>
                    </div>
                    <div>
                        <span>Tanggal</span>
                        <strong>{{ selectedResultsBooking.date }}</strong>
                    </div>
                    <div>
                        <span>Waktu</span>
                        <strong>{{ selectedResultsBooking.time }}</strong>
                    </div>
                    <div class="sm:col-span-2">
                        <span>Judul rapat</span>
                        <strong>{{ selectedResultsBooking.title }}</strong>
                    </div>
                </div>

                <BookingResults
                    :key="selectedResultsBooking.id"
                    :booking-id="selectedResultsBooking.id"
                    :routes="{
                        documentation: documentation.url,
                        meetingMinutes: meetingMinutes.url,
                        destroy: destroyResult.url,
                        show: showResult.url,
                    }"
                    :documentations="selectedResultsBooking.documentations"
                    :meeting-minutes="selectedResultsBooking.meeting_minutes"
                    :editable="selectedResultsBooking.results_available"
                    @busy="resultsBusy = $event"
                />
            </div>
            <template #actions>
                <button
                    type="button"
                    class="ui-button"
                    :disabled="resultsBusy"
                    @click="closeResultsModal"
                >
                    Tutup
                </button>
            </template>
        </AppModal>

        <ConfirmModal
            v-if="bookingToCancel !== null"
            title="Batalkan Peminjaman"
            message="Apakah Anda yakin ingin membatalkan peminjaman ini?"
            confirm-label="Batalkan Peminjaman"
            :processing="isCancelling"
            @close="closeCancelModal"
            @confirm="cancelBooking"
        />
        <ConfirmModal
            v-if="bookingToFinish !== null"
            title="Akhiri Peminjaman"
            message="Apakah Anda yakin ingin mengakhiri peminjaman ini sekarang?"
            confirm-label="Akhiri Sekarang"
            :processing="isFinishing"
            @close="closeFinishModal"
            @confirm="finishBooking"
        />
        <BookingEditModal
            v-if="editingBooking"
            :key="editingBooking.id"
            :booking="editingBooking"
            :update-url="update.url"
            @close="closeEditModal"
        />
    </div>
</template>

<style scoped>
.page-card {
    display: grid;
    min-width: 0;
    gap: var(--ui-gap);
}

.filter {
    width: min(100%, 280px);
}

.table-card {
    min-width: 0;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    text-align: left;
    color: #1b3768;
    padding: 12px 16px;
    font-weight: 600;
}

td {
    padding: 12px 16px;
    color: var(--ui-text);
}

tbody tr:hover {
    background: #f6f8fc;
}

.empty {
    text-align: center;
    color: gray;
    padding: 40px;
}

@media (max-width: 768px) {
    table {
        min-width: 650px;
    }
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.results-cell {
    min-width: 150px;
}

.result-status {
    display: inline-block;
    margin-bottom: 8px;
    border-radius: 999px;
    padding: 3px 9px;
    font-size: 12px;
    font-weight: 600;
}

.belum-dilengkapi {
    background: #f1f5f9;
    color: #475569;
}

.sebagian {
    background: #fef3c7;
    color: #92400e;
}

.lengkap {
    background: #dcfce7;
    color: #15803d;
}

.result-summary {
    margin-top: 4px;
    color: #64748b;
    font-size: 12px;
}

.result-action-button {
    display: inline-flex;
    height: 32px;
    width: 32px;
    align-items: center;
    justify-content: center;
    border: 1px solid #93c5fd;
    border-radius: 6px;
    color: #2563eb;
    transition: background-color 0.2s;
}

.result-action-button:hover {
    background: #eff6ff;
}

.results-modal-content {
    display: grid;
    gap: 20px;
}

.booking-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    border-radius: 10px;
    background: #f8fafc;
    padding: 14px;
}

.booking-summary span {
    display: block;
    color: #64748b;
    font-size: 12px;
}

.booking-summary strong {
    display: block;
    margin-top: 3px;
    color: #0f172a;
    font-size: 14px;
}

@media (max-width: 640px) {
    .booking-summary {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
