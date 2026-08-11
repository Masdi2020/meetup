<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

interface AuditUser {
    id: number;
    name: string;
    role: string;
}

interface Audit {
    id: number;
    entity_type: string | null;
    entity_id: number | null;
    action: string;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    comment: string | null;
    created_at: string;
    user: AuditUser | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedAudits {
    data: Audit[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface Stats {
    total: number;
    today: number;
    admin: number;
    user: number;
}

const props = defineProps<{
    audits: PaginatedAudits;
    stats: Stats;
    filters: {
        search: string;
        role: string;
    };
}>();

const search = ref(props.filters.search);
const roleFilter = ref(props.filters.role);

const selectedAudit = ref<Audit | null>(null);
const showDetail = ref(false);

const filteredCount = computed(() => props.audits.total);

function applyFilter() {
    router.get(
        '/admin/audits',
        {
            search: search.value || undefined,
            role: roleFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function clearFilter() {
    search.value = '';
    roleFilter.value = '';

    applyFilter();
}

function showAuditDetail(audit: Audit) {
    selectedAudit.value = audit;
    showDetail.value = true;
}

function closeDetail() {
    showDetail.value = false;
    selectedAudit.value = null;
}

function exportCsv() {
    const params = new URLSearchParams();

    if (search.value) {
        params.append('search', search.value);
    }

    if (roleFilter.value) {
        params.append('role', roleFilter.value);
    }

    const query = params.toString();

    window.location.href =
        '/audits/export' + (query ? `?${query}` : '');
}

function formatDate(date: string) {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}

function formatRole(role?: string | null) {
    if (!role) {
        return '-';
    }

    return role.charAt(0).toUpperCase() + role.slice(1);
}

function badge(role?: string | null) {
    return role === 'admin'
        ? 'bg-indigo-100 text-indigo-700'
        : 'bg-green-100 text-green-700';
}

function formatEntity(entityType?: string | null) {
    if (!entityType) {
        return '-';
    }

    const parts = entityType.split('\\');

    return parts[parts.length - 1];
}

function formatValue(value: unknown) {
    if (value === null || value === undefined) {
        return '-';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }

    return String(value);
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Audit Log</h1>

                <p class="text-gray-500">
                    Riwayat seluruh aktivitas pada sistem.
                </p>
            </div>

            <button
                type="button"
                class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
                @click="exportCsv"
            >
                Export CSV
            </button>
        </div>

        <!-- Statistics -->
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Total Aktivitas</p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ props.stats.total }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Hari Ini</p>

                <h2 class="mt-2 text-3xl font-bold text-blue-600">
                    {{ props.stats.today }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Aktivitas Admin</p>

                <h2 class="mt-2 text-3xl font-bold text-indigo-600">
                    {{ props.stats.admin }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Aktivitas User</p>

                <h2 class="mt-2 text-3xl font-bold text-green-600">
                    {{ props.stats.user }}
                </h2>
            </div>
        </div>

        <!-- Filter -->
        <div class="rounded-xl bg-white p-5 shadow">
            <div class="grid gap-4 md:grid-cols-[1fr_220px_auto_auto]">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari aktivitas..."
                    class="rounded-lg border px-4 py-2"
                    @keyup.enter="applyFilter"
                />

                <select
                    v-model="roleFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Role</option>

                    <option value="admin">Admin</option>

                    <option value="user">User</option>
                </select>

                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-white hover:bg-blue-700"
                    @click="applyFilter"
                >
                    Cari
                </button>

                <button
                    type="button"
                    class="rounded-lg border px-5 py-2 hover:bg-gray-100"
                    @click="clearFilter"
                >
                    Reset
                </button>
            </div>

            <div class="mt-3 text-sm text-gray-500">
                Menampilkan {{ props.audits.data.length }}
                dari {{ filteredCount }} aktivitas
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl bg-white shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr class="text-left text-sm">
                            <th class="px-5 py-4">Waktu</th>

                            <th class="px-5 py-4">Pengguna</th>

                            <th class="px-5 py-4">Aktivitas</th>

                            <th class="px-5 py-4">IP</th>

                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="audit in props.audits.data"
                            :key="audit.id"
                            class="border-t hover:bg-gray-50"
                        >
                            <!-- Waktu -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                {{ formatDate(audit.created_at) }}
                            </td>

                            <!-- User -->
                            <td class="px-5 py-4">
                                <div>
                                    <div class="font-medium">
                                        {{ audit.user?.name ?? '-' }}
                                    </div>

                                    <span
                                        v-if="audit.user"
                                        :class="badge(audit.user.role)"
                                        class="rounded-full px-2 py-1 text-xs font-semibold"
                                    >
                                        {{ formatRole(audit.user.role) }}
                                    </span>
                                </div>
                            </td>

                            <!-- Activity -->
                            <td class="px-5 py-4">
                                <div class="font-medium">
                                    {{ audit.action }}
                                </div>

                                <div
                                    v-if="audit.comment"
                                    class="text-sm text-gray-500"
                                >
                                    {{ audit.comment }}
                                </div>

                                <div
                                    v-if="audit.entity_type"
                                    class="mt-1 text-xs text-gray-400"
                                >
                                    {{ formatEntity(audit.entity_type) }}
                                    #{{ audit.entity_id }}
                                </div>
                            </td>

                            <!-- IP -->
                            <td class="px-5 py-4">
                                {{ audit.ip_address ?? '-' }}
                            </td>

                            <!-- Action -->
                            <td class="px-5 py-4">
                                <div class="flex justify-end">
                                    <button
                                        type="button"
                                        class="rounded-lg border px-3 py-2 hover:bg-gray-100"
                                        @click="showAuditDetail(audit)"
                                    >
                                        Detail
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="props.audits.data.length === 0">
                            <td
                                colspan="5"
                                class="px-5 py-10 text-center text-gray-500"
                            >
                                Tidak ada aktivitas ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="props.audits.last_page > 1"
                class="flex flex-wrap items-center justify-center gap-2 border-t p-4"
            >
                <template
                    v-for="(link, index) in props.audits.links"
                    :key="index"
                >
                    <button
                        v-if="link.url"
                        type="button"
                        class="rounded-lg border px-3 py-2 text-sm"
                        :class="
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'hover:bg-gray-100'
                        "
                        @click="
                            router.visit(link.url, {
                                preserveState: true,
                                preserveScroll: true,
                            })
                        "
                        v-html="link.label"
                    />

                    <span
                        v-else
                        class="px-3 py-2 text-sm text-gray-400"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <!-- Detail Modal -->
        <div
            v-if="showDetail && selectedAudit"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="closeDetail"
        >
            <div
                class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white shadow-xl"
            >
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b p-5">
                    <div>
                        <h2 class="text-xl font-bold">
                            Detail Audit #{{ selectedAudit.id }}
                        </h2>

                        <p class="text-sm text-gray-500">
                            {{ formatDate(selectedAudit.created_at) }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-gray-500 hover:bg-gray-100"
                        @click="closeDetail"
                    >
                        ✕
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="space-y-5 p-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-sm text-gray-500">Pengguna</p>

                            <p class="font-medium">
                                {{ selectedAudit.user?.name ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Role</p>

                            <p class="font-medium">
                                {{
                                    formatRole(
                                        selectedAudit.user?.role ?? null,
                                    )
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Aktivitas</p>

                            <p class="font-medium">
                                {{ selectedAudit.action }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">IP Address</p>

                            <p class="font-medium">
                                {{ selectedAudit.ip_address ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Entity</p>

                            <p class="font-medium">
                                {{ formatEntity(selectedAudit.entity_type) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Entity ID</p>

                            <p class="font-medium">
                                {{ selectedAudit.entity_id ?? '-' }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Komentar</p>

                        <p class="mt-1">
                            {{ selectedAudit.comment ?? '-' }}
                        </p>
                    </div>

                    <!-- Old Values -->
                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-500">
                            Data Sebelum
                        </p>

                        <pre
                            class="overflow-x-auto rounded-lg bg-gray-100 p-4 text-sm"
                        >{{ formatValue(selectedAudit.old_values) }}</pre>
                    </div>

                    <!-- New Values -->
                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-500">
                            Data Sesudah
                        </p>

                        <pre
                            class="overflow-x-auto rounded-lg bg-gray-100 p-4 text-sm"
                        >{{ formatValue(selectedAudit.new_values) }}</pre>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end border-t p-5">
                    <button
                        type="button"
                        class="rounded-lg border px-5 py-2 hover:bg-gray-100"
                        @click="closeDetail"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
