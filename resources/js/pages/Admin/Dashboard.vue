<script setup lang="ts">
import AdminLayout from "@/layouts/AdminLayout.vue";

defineOptions({
    layout: AdminLayout,
});

interface Stats {
    rooms: number;
    users: number;
    bookings: number;
    pending: number;
}

interface TodayBooking {
    id: number;
    title: string;
    start_time: string;
    room: {
        name: string;
    };
}

interface Activity {
    id: number;
    booking: {
        title: string;
    };
    changed_by: {
        name: string;
    }
    old_status: {
        label: string;
    } | null;
    new_status: {
        label: string;
    };
    created_at: string;
}

defineProps<{
    stats: Stats;
    todayBookings: TodayBooking[];
    activities: Activity[];
}>();
</script>

<template>
    <div class="space-y-8">

        <div>
            <h1 class="text-3xl font-bold">
                Dashboard Admin
            </h1>

            <p class="text-gray-500">
                Selamat datang kembali.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

            <div class="rounded-xl bg-white shadow p-6">
                <p class="text-gray-500">
                    Total Ruangan
                </p>

                <h2 class="text-4xl font-bold mt-2">
                    {{ stats.rooms }}
                </h2>
            </div>

            <div class="rounded-xl bg-white shadow p-6">
                <p class="text-gray-500">
                    Total Pengguna
                </p>

                <h2 class="text-4xl font-bold mt-2">
                    {{ stats.users }}
                </h2>
            </div>

            <div class="rounded-xl bg-white shadow p-6">
                <p class="text-gray-500">
                    Total Booking
                </p>

                <h2 class="text-4xl font-bold mt-2">
                    {{ stats.bookings }}
                </h2>
            </div>

            <div class="rounded-xl bg-white shadow p-6 border-l-4 border-yellow-500">
                <p class="text-gray-500">
                    Pending Approval
                </p>

                <h2 class="text-4xl font-bold mt-2 text-yellow-600">
                    {{ stats.pending }}
                </h2>
            </div>

        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            <div class="bg-white rounded-xl shadow p-6">

                <h2 class="font-semibold text-lg mb-4">
                    Booking Hari Ini
                </h2>

                <div class="space-y-3">

                    <div
                        v-for="booking in todayBookings"
                        :key="booking.id"
                        class="flex justify-between border rounded-lg p-3"
                    >
                        <div>
                            <p class="font-medium">
                                {{ booking.title }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ booking.room.name }}
                            </p>
                        </div>

                        <span>
                            {{ booking.start_time.substring(0, 5) }}
                        </span>
                    </div>

                    <p
                        v-if="todayBookings.length === 0"
                        class="text-gray-500"
                    >
                        Tidak ada booking hari ini.
                    </p>
                </div>

            </div>

            <div class="bg-white rounded-xl shadow p-6">

                <h2 class="font-semibold text-lg mb-4">
                    Aktivitas Terbaru
                </h2>

                <ul class="space-y-3">
                    <li
                        v-for="activity in activities"
                        :key="activity.id"
                        class="border-b pb-2"
                    >
                        <strong>{{ activity.changed_by.name }}</strong>

                        mengubah status

                        <strong>{{ activity.booking.title }}</strong>

                        dari

                        {{ activity.old_status?.label ?? "-" }}

                        menjadi

                        {{ activity.new_status.label }}
                    </li>
                </ul>

            </div>

        </div>

    </div>
</template>
