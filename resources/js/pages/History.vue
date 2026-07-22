<script setup lang="ts">
import { computed, ref } from "vue";

type Status = "Approved" | "Pending" | "Rejected" | "Cancelled";

interface BookingHistory {
    id: number;
    room: string;
    date: string;
    time: string;
    title: string;
    status: Status;
}

const filterStatus = ref("Semua");

const histories = ref<BookingHistory[]>([
    {
        id: 1,
        room: "Ruang Rapat A",
        date: "10 Juli 2026",
        time: "07.30-09.30",
        title: "Neraca",
        status: "Approved",
    },
    {
        id: 2,
        room: "Ruang Rapat B",
        date: "18 Juli 2026",
        time: "12.30-14.30",
        title: "Monev SE2026",
        status: "Pending",
    },
]);

const filteredHistory = computed(() => {
    if (filterStatus.value === "Semua") {
        return histories.value;
    }

    return histories.value.filter(
        (item) => item.status === filterStatus.value
    );
});

const editBooking = (booking: BookingHistory) => {
    console.log("Edit", booking);

    // Contoh jika menggunakan Inertia
    // router.visit(`/booking/${booking.id}/edit`);
};

const cancelBooking = (id: number) => {
    if (!confirm("Batalkan peminjaman ini?")) {
        return;
    }

    console.log("Cancel", id);

    // Contoh Inertia
    // router.delete(`/booking/${id}`);
};
</script>

<template>
    <div class="history-page">

        <h2>Riwayat Peminjaman Ruang Rapat</h2>

        <div class="page-card">

            <div class="filter">
                <select v-model="filterStatus">
                    <option>Semua</option>
                    <option>Approved</option>
                    <option>Pending</option>
                    <option>Rejected</option>
                    <option>Cancelled</option>
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
                                v-if="item.status === 'Pending'"
                                class="action-buttons"
                            >
                                <button
                                    class="edit-btn"
                                    @click="editBooking(item)"
                                >
                                    Edit
                                </button>

                                <button
                                    class="cancel-btn"
                                    @click="cancelBooking(item.id)"
                                >
                                    Cancel
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="filteredHistory.length === 0">
                        <td
                            colspan="7"
                            class="empty"
                        >
                            Tidak ada data.
                        </td>
                    </tr>

                </tbody>

            </table>

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
    box-shadow: 0 0 8px rgba(0,0,0,.08);
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
    transition: .2s;
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
</style>
