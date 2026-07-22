<script setup lang="ts">
import { ref } from "vue";

const selectedRoom = ref("Ruang Rapat A (Besar)");

const rooms = [
    "Ruang Rapat A (Besar)",
    "Ruang Rapat B (Sedang)",
    "Ruang Meeting C (Kecil)",
];

const form = ref({
    date: "",
    startTime: "",
    endTime: "",
    title: "",
    participants: "",
    request: "",
    banner: null as File | null,
});

const times = [];

for (let hour = 7; hour <= 21; hour++) {
    times.push(`${hour.toString().padStart(2, "0")}:00`);

    if (hour !== 21) {
        times.push(`${hour.toString().padStart(2, "0")}:30`);
    }
}

const handleFile = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        form.value.banner = target.files[0];
    }
};

const submitBooking = () => {
    console.log({
        room: selectedRoom.value,
        ...form.value,
    });

    alert("Booking berhasil dikirim.");
};
</script>

<template>
    <div class="booking-page">

        <h2>Formulir Peminjaman</h2>

        <div class="page-card">

            <div class="room-selector">
            <select v-model="selectedRoom">
                <option
                    v-for="room in rooms"
                    :key="room"
                    :value="room"
                >
                    {{ room }}
                </option>
            </select>
            </div>

            <div class="booking-card">

            <div class="form-group">
                <label>Tanggal</label>
                <input
                    type="date"
                    v-model="form.date"
                >
            </div>

            <div class="form-group">
                <label>Dari Jam</label>
                <select v-model="form.startTime">
                    <option value="">Pilih Jam</option>

                    <option
                        v-for="time in times"
                        :key="time"
                    >
                        {{ time }}
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>Sampai Jam</label>
                <select v-model="form.endTime">
                    <option value="">Pilih Jam</option>

                    <option
                        v-for="time in times"
                        :key="time"
                    >
                        {{ time }}
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>Judul Rapat</label>
                <input
                    type="text"
                    v-model="form.title"
                >
            </div>

            <div class="form-group">
                <label>Jumlah Orang</label>
                <input
                    type="number"
                    min="1"
                    v-model="form.participants"
                >
            </div>

            <div class="form-group">
                <label>Permintaan Khusus</label>
                <textarea
                    rows="3"
                    placeholder="contoh: penataan meja dan kursi"
                    v-model="form.request"
                ></textarea>
            </div>

            <div class="form-group">
                <label>Unggah Banner Rapat</label>

                <input
                    type="file"
                    accept=".jpg,.jpeg,.png"
                    @change="handleFile"
                >
            </div>

            <div class="button-wrapper">
                <button @click="submitBooking">
                    Booking
                </button>
            </div>

            </div>

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
    transition: .2s;
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
</style>
