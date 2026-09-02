<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ActionIconButton from '@/components/atoms/ActionIconButton.vue';
import AppInput from '@/components/atoms/AppInput.vue';
import ConfirmModal from '@/components/organisms/ConfirmModal.vue';

type Status = 'Approved' | 'Pending' | 'Rejected' | 'Cancelled' | 'Finished';

interface BookingHistory {
    id: number;
    room: string;
    date: string;
    time: string;
    title: string;
    status: Status;
}

interface BookingStatusOption {
    value: Status;
    label: string;
}

const showEditModal = ref(false);
const showDateAlertModal = ref(false);
const bookingToCancel = ref<number | null>(null);
const isCancelling = ref(false);
const bookingToFinish = ref<number | null>(null);
const isFinishing = ref(false);
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

const filterStatus = ref<Status | ''>('');

const { histories, statuses } = defineProps<{
    histories: BookingHistory[];
    statuses: BookingStatusOption[];
}>();

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
    <div class="history-page">
        <h2>Riwayat Peminjaman Ruang Rapat</h2>

        <div class="page-card">
            <div class="filter">
                <select v-model="filterStatus">
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

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Ruang Rapat</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Judul</th>
                            <th>Status</th>
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

                            <td>
                                <div
                                    v-if="
                                        item.status === 'Pending' ||
                                        item.status === 'Approved'
                                    "
                                    class="action-buttons"
                                >
                                    <ActionIconButton
                                        action="edit"
                                        @click="openEditModal(item)"
                                    />

                                    <ActionIconButton
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
                            <td colspan="7" class="empty">Tidak ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div v-if="showDateAlertModal" class="modal-overlay">
        <div
            class="modal"
            role="alertdialog"
            aria-modal="true"
            aria-label="Tanggal tidak valid"
        >
            <h3>Tanggal Tidak Valid</h3>
            <p>Tanggal tidak boleh kurang dari hari ini.</p>
            <div class="modal-actions">
                <button class="edit-btn" @click="showDateAlertModal = false">
                    Mengerti
                </button>
            </div>
        </div>
    </div>

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
    <div v-if="showEditModal" class="modal-overlay">
        <div class="modal">
            <h3>Edit Booking</h3>

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

            <div class="modal-actions">
                <button class="cancel-btn" @click="closeEditModal">
                    Batal
                </button>

                <button class="edit-btn" @click="submitEdit">Simpan</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.history-page {
    padding: 30px;
}

.page-card {
    background: #cfe2ff;
    border-radius: 10px;
    padding: 28px;
    max-width: 1100px;
    margin: 0; /* align left with heading */
}

h2 {
    width: fit-content;
    color: #1b3768;
    border-bottom: 2px solid #d9d9d9;
    padding-bottom: 5px;
    margin-bottom: 15px;
}

.filter {
    margin-bottom: 20px;
}

.filter select {
    background: #efc74a;
    border: none;
    border-radius: 8px;
    padding: 8px 14px;
    font-weight: 600;
    cursor: pointer;
}

.table-card {
    background: white;
    border-radius: 12px;
    padding: 18px;
    min-height: 420px;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.08);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    text-align: left;
    color: #1b3768;
    padding: 10px 14px;
    font-weight: 600;
}

td {
    padding: 10px 14px;
    color: #243b73;
}

tbody tr:hover {
    background: #f6f8fc;
}

.badge {
    display: inline-block;
    min-width: 85px;
    text-align: center;
    padding: 4px 12px;
    border-radius: 6px;
    color: white;
    font-size: 13px;
    font-weight: 600;
}

.approved {
    background: #2da10c;
}

.pending {
    background: #d8bc00;
}

.rejected {
    background: #d62828;
}

.cancelled {
    background: #6c757d;
}

.empty {
    text-align: center;
    color: gray;
    padding: 40px;
}

@media (max-width: 768px) {
    .history-page {
        padding: 15px;
    }

    table {
        min-width: 650px;
    }
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.edit-btn,
.cancel-btn {
    border: none;
    border-radius: 6px;
    padding: 5px 12px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
}

.edit-btn {
    background: #2b6cb0;
    color: white;
}

.edit-btn:hover {
    background: #1f4f82;
}

.cancel-btn {
    background: #dc3545;
    color: white;
}

.cancel-btn:hover {
    background: #b52b38;
}

.finished {
    background: #2563eb;
}

.finish-btn {
    border: none;
    border-radius: 6px;
    padding: 5px 12px;
    cursor: pointer;
    background: #2563eb;
    color: white;
    font-size: 13px;
    font-weight: 600;
}

.finish-btn:hover {
    background: #1d4ed8;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);

    display: flex;
    justify-content: center;
    align-items: center;

    z-index: 999;
}

.modal {
    width: 500px;
    max-width: 95%;

    background: white;
    border-radius: 12px;
    padding: 24px;
}

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.form-group input {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.save-btn {
    background: #2563eb;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    cursor: pointer;
}

.close-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    cursor: pointer;
}
</style>
