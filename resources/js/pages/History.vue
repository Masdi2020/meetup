<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ActionIconButton from '@/components/atoms/ActionIconButton.vue';
import AppInput from '@/components/atoms/AppInput.vue';
import AppModal from '@/components/organisms/AppModal.vue';
import BookingResults from '@/components/organisms/BookingResults.vue';
import ConfirmModal from '@/components/organisms/ConfirmModal.vue';
import { useBookingPageUpdates } from '@/composables/useBookingUpdates';
import type {
    BookingHistory,
    BookingHistoryStatus,
    HistoryStatusOption,
} from '@/types/booking';

const showEditModal = ref(false);
const showDateAlertModal = ref(false);
const bookingToCancel = ref<number | null>(null);
const isCancelling = ref(false);
const bookingToFinish = ref<number | null>(null);
const isFinishing = ref(false);
const resultsBusy = ref(false);
const selectedResultsBooking = ref<BookingHistory | null>(null);
const showResultsModal = ref(false);
const today = new Date().toISOString().split('T')[0];

const editForm = ref({
    id: 0,
    title: '',
    date: '',
    start_time: '',
    end_time: '',
});

const formatBookingDate = (value: string) => {
    if (!value) {
        return '';
    }

    const formattedDate = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value);

    if (formattedDate) {
        return `${formattedDate[3]}-${formattedDate[2]}-${formattedDate[1]}`;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
};

const openEditModal = (booking: BookingHistory) => {
    const [start, end] = booking.time.split(' - ');

    editForm.value = {
        id: booking.id,
        title: booking.title,
        date: formatBookingDate(booking.date),
        start_time: start,
        end_time: end,
    };

    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
};

const submitEdit = () => {
    if (editForm.value.date && editForm.value.date < today) {
        showDateAlertModal.value = true;

        return;
    }

    router.put(`/booking/${editForm.value.id}`, editForm.value, {
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const filterStatus = ref<BookingHistoryStatus | ''>('');

const { histories, statuses } = defineProps<{
    histories: BookingHistory[];
    statuses: HistoryStatusOption[];
}>();

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
    () => histories,
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

        if (showEditModal.value && !canEdit(editForm.value.id)) {
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

const filteredHistory = computed(() => {
    if (!filterStatus.value) {
        return histories;
    }

    return histories.filter((history) => history.status === filterStatus.value);
});

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
        `/booking/${bookingToCancel.value}/cancel`,
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
        '/booking/' + bookingToFinish.value + '/finish',
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
        <header class="page-header page-heading">
            <h1 class="page-title">Riwayat Peminjaman Ruang Rapat</h1>
        </header>

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
                            v-for="(item, index) in filteredHistory"
                            :key="item.id"
                        >
                            <td>{{ index + 1 }}</td>
                            <td>{{ item.room }}</td>
                            <td>{{ item.date }}</td>
                            <td>{{ item.time }}</td>
                            <td>{{ item.title }}</td>

                            <td>
                                <span
                                    class="badge"
                                    :class="item.status.toLowerCase()"
                                >
                                    {{ item.status }}
                                </span>
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

                        <tr v-if="filteredHistory.length === 0">
                            <td colspan="8" class="empty">Tidak ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
                :base-url="`/booking/${selectedResultsBooking.id}`"
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

    <AppModal
        v-if="showDateAlertModal"
        title="Tanggal Tidak Valid"
        max-width="md"
        @close="showDateAlertModal = false"
    >
        <p>Tanggal tidak boleh kurang dari hari ini.</p>
        <template #actions
            ><button
                class="ui-button ui-button--primary"
                @click="showDateAlertModal = false"
            >
                Mengerti
            </button></template
        >
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
    <AppModal v-if="showEditModal" title="Edit Booking" @close="closeEditModal">
        <div class="form-group">
            <label>Judul</label>
            <input type="text" v-model="editForm.title" />
        </div>

        <div class="form-group">
            <label>Tanggal</label>
            <AppInput v-model="editForm.date" type="date" :min="today" />
        </div>

        <div class="form-group">
            <label>Jam Mulai</label>
            <AppInput v-model="editForm.start_time" type="time" />
        </div>

        <div class="form-group">
            <label>Jam Selesai</label>
            <AppInput v-model="editForm.end_time" type="time" />
        </div>

        <template #actions>
            <button class="ui-button" @click="closeEditModal">Batal</button>

            <button class="ui-button ui-button--primary" @click="submitEdit">
                Simpan
            </button>
        </template>
    </AppModal>
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

.badge {
    display: inline-block;
    min-width: 85px;
    text-align: center;
    padding: 4px 12px;
    border-radius: 999px;
    color: white;
    font-size: 13px;
    font-weight: 600;
}

.approved {
    background: #dcfce7;
    color: #15803d;
}

.pending {
    background: #fef3c7;
    color: #92400e;
}

.rejected {
    background: #fee2e2;
    color: #b91c1c;
}

.cancelled {
    background: #f1f5f9;
    color: #475569;
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

.finished {
    background: #dbeafe;
    color: #1d4ed8;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: var(--ui-gap);
}

.form-group input {
    min-height: var(--ui-control-height);
    padding: 10px 12px;
    border: 1px solid var(--ui-border);
    border-radius: 8px;
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
