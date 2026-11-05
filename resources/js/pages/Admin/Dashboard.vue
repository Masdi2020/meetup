<script setup lang="ts">
import { PageHeader, StatCard } from '@/components/molecules';
import type {
    AuditLog as Activity,
    DashboardStats as Stats,
    TodayBooking,
} from '@/types/admin';
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
    activities: Activity[] | null;
}>();
</script>

<template>
    <div class="app-page">
        <PageHeader
            title="Dashboard Admin"
            description="Selamat datang kembali."
        />

        <div class="stats-grid">
            <StatCard label="Total Ruangan" :value="stats.rooms" size="large" />
            <StatCard
                label="Total Pengguna"
                :value="stats.users"
                size="large"
            />
            <StatCard
                label="Total Peminjaman"
                :value="stats.bookings"
                size="large"
            />
            <StatCard
                label="Menunggu Persetujuan"
                :value="stats.pending"
                tone="warning"
                size="large"
                accent
            />
        </div>
        <div
            class="grid grid-cols-1 gap-section"
            :class="{ 'xl:grid-cols-2': activities }"
        >
            <div class="ui-card ui-card-body">
                <h2 class="mb-4 text-lg font-semibold">Peminjaman Hari Ini</h2>

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

                    <div
                        v-if="todayBookings.length === 0"
                        class="rounded-xl border border-dashed bg-slate-50 px-5 py-10 text-center"
                    >
                        <p class="font-medium text-slate-700">
                            Belum ada peminjaman hari ini
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Jadwal peminjaman hari ini akan muncul di sini.
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="activities" class="ui-card ui-card-body">
                <h2 class="mb-4 text-lg font-semibold">Aktivitas Terbaru</h2>

                <div class="space-y-field">
                    <div
                        v-for="activity in activities"
                        :key="activity.id"
                        class="flex gap-field border-b pb-4 last:border-0"
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
