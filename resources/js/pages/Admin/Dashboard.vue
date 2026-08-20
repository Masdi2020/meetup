<script setup lang="ts">
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
    entity_type: string | null;
    entity_id: number | null;
    action: string;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    changed_by: number | null;
    ip_address: string | null;
    comment: string | null;
    created_at: string;

    user: {
        id: number;
        name: string;
        role: string;
    } | null;
}

function getEntityName(entityType: string | null) {
    if (!entityType) {
        return 'Data';
    }

    const parts = entityType.split('\\');

    return parts[parts.length - 1];
}

function formatTime(date: string) {
    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));
}

function formatDateTime(date: string) {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
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
            <h1 class="text-3xl font-bold">Dashboard Admin</h1>

            <p class="text-gray-500">Selamat datang kembali.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-white p-6 shadow">
                <p class="text-gray-500">Total Ruangan</p>

                <h2 class="mt-2 text-4xl font-bold">
                    {{ stats.rooms }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-6 shadow">
                <p class="text-gray-500">Total Pengguna</p>

                <h2 class="mt-2 text-4xl font-bold">
                    {{ stats.users }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-6 shadow">
                <p class="text-gray-500">Total Booking</p>

                <h2 class="mt-2 text-4xl font-bold">
                    {{ stats.bookings }}
                </h2>
            </div>

            <div
                class="rounded-xl border-l-4 border-yellow-500 bg-white p-6 shadow"
            >
                <p class="text-gray-500">Pending Approval</p>

                <h2 class="mt-2 text-4xl font-bold text-yellow-600">
                    {{ stats.pending }}
                </h2>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-xl bg-white p-6 shadow">
                <h2 class="mb-4 text-lg font-semibold">Booking Hari Ini</h2>

                <div class="space-y-3">
                    <div
                        v-for="booking in todayBookings"
                        :key="booking.id"
                        class="flex justify-between rounded-lg border p-3"
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

                    <p v-if="todayBookings.length === 0" class="text-gray-500">
                        Tidak ada booking hari ini.
                    </p>
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow">
                <h2 class="mb-4 text-lg font-semibold">Aktivitas Terbaru</h2>

                <div class="space-y-4">
                    <div
                        v-for="activity in activities"
                        :key="activity.id"
                        class="flex gap-4 border-b pb-4 last:border-0"
                    >
                        <!-- Icon -->
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600"
                        >
                            <span class="text-sm font-bold">
                                {{
                                    activity.user?.name
                                        ?.charAt(0)
                                        .toUpperCase() ?? 'A'
                                }}
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium">
                                        {{ activity.user?.name ?? 'System' }}
                                    </p>

                                    <p class="text-sm text-gray-500">
                                        {{ activity.action }}
                                    </p>
                                </div>

                                <span
                                    class="shrink-0 text-xs text-gray-400"
                                    :title="formatDateTime(activity.created_at)"
                                >
                                    {{ formatTime(activity.created_at) }}
                                </span>
                            </div>

                            <div class="mt-2 text-sm text-gray-600">
                                <span>
                                    {{ getEntityName(activity.entity_type) }}
                                </span>

                                <span
                                    v-if="activity.entity_id"
                                    class="text-gray-400"
                                >
                                    #{{ activity.entity_id }}
                                </span>
                            </div>

                            <p
                                v-if="activity.comment"
                                class="mt-1 text-sm text-gray-500"
                            >
                                {{ activity.comment }}
                            </p>
                        </div>
                    </div>

                    <p
                        v-if="activities.length === 0"
                        class="py-6 text-center text-gray-500"
                    >
                        Belum ada aktivitas.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
