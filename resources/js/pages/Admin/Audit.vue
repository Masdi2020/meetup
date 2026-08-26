<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppInput from '@/components/atoms/AppInput.vue';
import AppSelect from '@/components/atoms/AppSelect.vue';
import StatCard from '@/components/molecules/StatCard.vue';
import DetailModal from '@/components/organisms/DetailModal.vue';
import { useModalManager } from '@/composables/useModal';
import type {
    AuditLog as Audit,
    AuditStats as Stats,
    PaginatedAudits,
} from '@/types/admin';

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
const { openModal, closeModal, isModalOpen } = useModalManager<'detail'>();
const showDetail = computed(() => isModalOpen('detail'));

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
    openModal('detail');
}

function closeDetail() {
    closeModal();
    selectedAudit.value = null;
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
    <div class="w-full max-w-7xl space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Audit Log</h1>

                <p class="text-gray-500">
                    Riwayat seluruh aktivitas pada sistem.
                </p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <StatCard label="Total Aktivitas" :value="props.stats.total" />
            <StatCard label="Hari Ini" :value="props.stats.today" tone="info" />
            <StatCard
                label="Aktivitas Admin"
                :value="props.stats.admin"
                tone="indigo"
            />
            <StatCard
                label="Aktivitas User"
                :value="props.stats.user"
                tone="success"
            />
        </div>

        <!-- Filter -->
        <div class="rounded-xl bg-white p-5 shadow">
            <div class="grid gap-4 md:grid-cols-[1fr_220px_auto_auto]">
                <AppInput
                    v-model="search"
                    appearance="admin"
                    type="text"
                    placeholder="Cari aktivitas..."
                    class="rounded-lg border px-4 py-2"
                    @keyup.enter="applyFilter"
                />

                <AppSelect
                    v-model="roleFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Role</option>

                    <option value="admin">Admin</option>

                    <option value="user">User</option>
                </AppSelect>

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
                Menampilkan {{ props.audits.data.length }} dari
                {{ filteredCount }} aktivitas
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-xl bg-white shadow">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
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

        <DetailModal
            v-if="showDetail && selectedAudit"
            :title="`Detail Audit #${selectedAudit.id}`"
            max-width="3xl"
            @close="closeDetail"
        >
            <p class="mb-5 text-sm text-gray-500">
                {{ formatDate(selectedAudit.created_at) }}
            </p>
            <div class="space-y-5">
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
                            {{ formatRole(selectedAudit.user?.role ?? null) }}
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
        </DetailModal>
    </div>
</template>
