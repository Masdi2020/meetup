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

defineProps<{
    stats: Stats;
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
                        class="flex justify-between border rounded-lg p-3"
                    >
                        <div>
                            <p class="font-medium">
                                Meeting Internal
                            </p>

                            <p class="text-sm text-gray-500">
                                Ruang A
                            </p>
                        </div>

                        <span>
                            09.00
                        </span>
                    </div>

                    <div
                        class="flex justify-between border rounded-lg p-3"
                    >
                        <div>
                            <p class="font-medium">
                                Presentasi
                            </p>

                            <p class="text-sm text-gray-500">
                                Ruang B
                            </p>
                        </div>

                        <span>
                            13.00
                        </span>
                    </div>

                </div>

            </div>

            <div class="bg-white rounded-xl shadow p-6">

                <h2 class="font-semibold text-lg mb-4">
                    Aktivitas Terbaru
                </h2>

                <ul class="space-y-3">

                    <li class="border-b pb-2">
                        Admin menyetujui booking Ruang A
                    </li>

                    <li class="border-b pb-2">
                        User melakukan booking Ruang C
                    </li>

                    <li>
                        User membatalkan booking
                    </li>

                </ul>

            </div>

        </div>

    </div>
</template>
