<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {  ref, watch } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";

defineOptions({
    layout: AdminLayout,
});

type BookingStatus =
    | "pending"
    | "approved"
    | "rejected"
    | "cancelled"
    | "finished";

interface Booking {
    id: number;
    code: string;
    room: string;
    borrower: string;
    activity: string;
    date: string;
    start: string;
    end: string;
    status: BookingStatus;
}

interface Room {
    id: number;
    name: string;
}

interface Stats {
    total: number;
    pending: number;
    approved: number;
    finished: number;
}

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    bookings?: Pagination<Booking>;
    rooms?: Room[];
    stats?: Stats;
    filters?: {
        search?: string;
        status?: string;
        room?: string;
    };
}>();

const search = ref(props.filters?.search ?? "");
const statusFilter = ref(props.filters?.status ?? "");
const roomFilter = ref(props.filters?.room ?? "");

watch(
    [search, statusFilter, roomFilter],
    () => {
        router.get(
            "/admin/bookings",
            {
                search: search.value,
                status: statusFilter.value,
                room: roomFilter.value,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    }
);

function badgeClass(status: BookingStatus) {
    return {
        pending:
            "bg-yellow-100 text-yellow-700",
        approved:
            "bg-green-100 text-green-700",
        rejected:
            "bg-red-100 text-red-700",
        cancelled:
            "bg-gray-200 text-gray-700",
        finished:
            "bg-blue-100 text-blue-700",
    }[status];
}
</script>

<template>
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">
                    Booking
                </h1>

                <p class="text-gray-500">
                    Kelola seluruh peminjaman ruangan.
                </p>
            </div>
        </div>

        <!-- Statistik -->

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    Total
                </p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ stats?.total }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    Pending
                </p>

                <h2 class="mt-2 text-3xl font-bold text-yellow-600">
                    {{ stats?.pending }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    Approved
                </p>

                <h2 class="mt-2 text-3xl font-bold text-green-600">
                    {{ stats?.approved }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    Selesai
                </p>

                <h2 class="mt-2 text-3xl font-bold text-blue-600">
                    {{ stats?.finished }}
                </h2>
            </div>

        </div>

        <!-- Filter -->

        <div class="rounded-xl bg-white p-5 shadow">

            <div class="grid gap-4 md:grid-cols-3">

                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari booking..."
                    class="rounded-lg border px-4 py-2 outline-none focus:border-blue-500"
                >

                <select
                    v-model="statusFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="finished">Finished</option>
                </select>

                <select
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
                </select>

            </div>

        </div>

        <!-- Table -->

        <div class="overflow-hidden rounded-xl bg-white shadow">

            <table class="min-w-full">

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
                                >
                                    Detail
                                </button>

                                <button
                                    v-if="booking.status === 'pending'"
                                    class="rounded-lg bg-green-600 px-3 py-2 text-sm text-white hover:bg-green-700"
                                >
                                    Approve
                                </button>

                                <button
                                    v-if="booking.status === 'pending'"
                                    class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                                >
                                    Reject
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
                    router.get(
                        '/admin/bookings', {
                            page: bookings?.current_page - 1,
                            search,
                            status: statusFilter,
                            room: roomFilter,
                        }
                    )
                "
            >
                Previous
            </button>

            <button
                class="rounded-lg border px-4 py-2 hover:bg-gray-100"
                :disabled="bookings?.current_page === bookings?.last_page"
                @click="
                    router.get(
                        '/admin/bookings', {
                            page: bookings?.current_page + 1,
                            search,
                            status: statusFilter,
                            room: roomFilter,
                        }
                    )
                "
            >
                Next
            </button>

        </div>

    </div>
</template>
