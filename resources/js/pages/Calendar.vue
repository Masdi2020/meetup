<template>
    <div class="availability">
        <h2 class="title">Ketersediaan Ruangan</h2>

        <div class="calendar-grid">
            <div
                v-for="room in rooms"
                :key="room.id"
                class="calendar-item"
                :class="{ 'full-width': room.full }"
            >
                <h3>{{ room.name }}</h3>

                <div class="calendar-card">
                    <iframe
                        :src="`${room.calendar}&showPrint=0&showTz=0`"
                        class="calendar-frame"
                        frameborder="0"
                        scrolling="no"
                    ></iframe>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.availability {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    padding: 24px;
    box-sizing: border-box;
}

.title {
    margin-bottom: 25px;
    color: #1e3a8a;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
    width: 100%;
}

.calendar-item {
    min-width: 0; /* penting agar item bisa mengecil */
}

.full-width {
    grid-column: 1 / -1;
}

.calendar-card {
    width: 100%;
    height: 650px;
    overflow: hidden;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.calendar-placeholder {
    height: 420px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #fafafa;
    color: #888;
    font-size: 20px;
}

.calendar-frame {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}

@media (max-width: 900px) {
    .calendar-grid {
        grid-template-columns: 1fr;
    }

    .full-width {
        grid-column: auto;
    }
}
</style>

<script setup lang="ts">
const rooms = [
    {
        id: 1,
        name: 'Ruang Rapat A (Besar)',
        calendar:
            'https://calendar.google.com/calendar/embed?src=CALENDAR_A&ctz=Asia%2FMakassar',
    },
    {
        id: 2,
        name: 'Ruang Rapat B (Besar)',
        calendar:
            'https://calendar.google.com/calendar/embed?src=CALENDAR_B&ctz=Asia%2FMakassar',
    },
    {
        id: 3,
        name: 'Ruang Rapat C (Sedang)',
        calendar:
            'https://calendar.google.com/calendar/embed?src=CALENDAR_C&ctz=Asia%2FMakassar',
        full: true,
    },
];
</script>
