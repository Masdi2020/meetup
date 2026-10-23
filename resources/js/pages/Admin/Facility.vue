<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ActionIconButton, AppButton, AppInput } from '@/components/atoms';
import { FormField, PageHeader, StatCard } from '@/components/molecules';
import {
    AdminSearchPanel,
    AppModal,
    ConfirmModal,
    DetailModal,
} from '@/components/organisms';
import { useAdminFilters } from '@/composables/admin/useAdminFilters';
import { useModalManager } from '@/composables/useModal';
import { index, store, update, destroy } from '@/routes/admin/facilities';
import type { Facility } from '@/types/admin';
const props = defineProps<{
    facilities: Facility[];
    filters: {
        search: string;
    };
}>();

const facilities = computed(() => props.facilities);
const search = ref(props.filters.search ?? '');
const showAddModal = computed(() => isModalOpen('add'));
const { openModal, closeModal, isModalOpen } = useModalManager<
    'add' | 'detail' | 'edit'
>();
const showDetailModal = computed(() => isModalOpen('detail'));
const showEditModal = computed(() => isModalOpen('edit'));
const selectedFacility = ref<Facility | null>(null);
const facilityToDelete = ref<Facility | null>(null);
const addFacilityForm = useForm({ name: '' });
const editFacilityForm = useForm({ name: '' });

const { applyFilters, resetFilters } = useAdminFilters(index.url(), {
    debouncedSources: [search],
    query: () => ({ search: search.value }),
    reset: () => {
        search.value = '';
    },
});

const totalUsage = computed(() =>
    facilities.value.reduce(
        (total, facility) => total + facility.rooms_count,
        0,
    ),
);

function openAddModal() {
    addFacilityForm.reset();
    openModal('add');
}

function closeAddModal() {
    closeModal();
    addFacilityForm.reset();
}

function submitAddFacility() {
    if (!addFacilityForm.name.trim()) {
        return;
    }

    addFacilityForm.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            closeAddModal();
        },
    });
}

function openDetailModal(facility: Facility) {
    selectedFacility.value = facility;
    openModal('detail');
}

function closeDetailModal() {
    closeModal();
    selectedFacility.value = null;
}

function openEditModal(facility: Facility) {
    selectedFacility.value = facility;
    editFacilityForm.name = facility.name;
    openModal('edit');
}

function closeEditModal() {
    closeModal();
    selectedFacility.value = null;
    editFacilityForm.reset();
}

function submitEditFacility() {
    if (!selectedFacility.value) {
        return;
    }

    const name = editFacilityForm.name.trim();

    if (!name) {
        return;
    }

    editFacilityForm.put(update.url(selectedFacility.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
}

function openDeleteModal(facility: Facility) {
    facilityToDelete.value = facility;
}

function closeDeleteModal() {
    facilityToDelete.value = null;
}

function deleteFacility() {
    if (!facilityToDelete.value) {
        return;
    }

    router.delete(destroy.url(facilityToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
}
</script>

<template>
    <div class="app-page">
        <!-- Header -->
        <PageHeader
            title="Fasilitas"
            description="Kelola fasilitas yang tersedia pada ruangan."
        >
            <AppButton variant="primary" size="lg" @click="openAddModal">
                + Tambah Fasilitas
            </AppButton>
        </PageHeader>

        <!-- Statistik -->
        <div class="stats-grid">
            <StatCard label="Total Fasilitas" :value="facilities.length" />
            <StatCard label="Digunakan di Ruangan" :value="totalUsage" />
        </div>

        <!-- Filter -->
        <AdminSearchPanel
            v-model="search"
            placeholder="Cari fasilitas..."
            @search="applyFilters"
            @reset="resetFilters"
        />

        <!-- Table -->
        <div class="ui-card overflow-x-auto">
            <table class="w-full min-w-[600px]">
                <thead class="bg-gray-100">
                    <tr class="text-left text-sm font-semibold">
                        <th class="px-5 py-4">Nama</th>

                        <th class="px-5 py-4">Digunakan</th>

                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="facility in facilities"
                        :key="facility.id"
                        class="border-t transition hover:bg-gray-50"
                    >
                        <td class="px-5 py-4 font-medium">
                            {{ facility.name }}
                        </td>

                        <td class="px-5 py-4">
                            {{ facility.rooms_count }} Ruangan
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <ActionIconButton
                                    action="detail"
                                    @click="openDetailModal(facility)"
                                />

                                <ActionIconButton
                                    action="edit"
                                    @click="openEditModal(facility)"
                                />

                                <ActionIconButton
                                    action="delete"
                                    @click="openDeleteModal(facility)"
                                />
                            </div>
                        </td>
                    </tr>

                    <tr v-if="facilities.length === 0">
                        <td colspan="3" class="py-10 text-center text-gray-500">
                            Tidak ada fasilitas yang ditemukan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppModal
            v-if="showAddModal"
            title="Tambah Fasilitas"
            max-width="md"
            @close="closeAddModal"
        >
            <div class="space-y-4">
                <FormField label="Nama fasilitas" appearance="admin" required
                    ><AppInput
                        v-model="addFacilityForm.name"
                        appearance="admin"
                        placeholder="Masukkan nama fasilitas"
                        required
                        @keyup.enter="submitAddFacility"
                /></FormField>
                <div class="flex justify-end gap-3 pt-2">
                    <AppButton
                        variant="neutral"
                        size="sm"
                        @click="closeAddModal"
                    >
                        Batal
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="sm"
                        @click="submitAddFacility"
                    >
                        Simpan
                    </AppButton>
                </div>
            </div>
        </AppModal>

        <DetailModal
            v-if="showDetailModal && selectedFacility"
            title="Detail Fasilitas"
            @close="closeDetailModal"
        >
            <div class="space-y-4">
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-sm text-gray-500">Nama fasilitas</p>
                    <p class="mt-1 text-lg font-semibold">
                        {{ selectedFacility.name }}
                    </p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-sm text-gray-500">Dipakai di ruangan</p>
                    <ul
                        v-if="selectedFacility.rooms.length"
                        class="mt-2 space-y-1"
                    >
                        <li
                            v-for="room in selectedFacility.rooms"
                            :key="room.id"
                            class="font-semibold"
                        >
                            {{ room.name }}
                        </li>
                    </ul>
                    <p v-else class="mt-1 text-gray-500">
                        Belum digunakan di ruangan mana pun.
                    </p>
                </div>
            </div>
        </DetailModal>

        <AppModal
            v-if="showEditModal && selectedFacility"
            title="Edit Fasilitas"
            max-width="md"
            @close="closeEditModal"
        >
            <div class="space-y-4">
                <FormField label="Nama fasilitas" appearance="admin" required
                    ><AppInput
                        v-model="editFacilityForm.name"
                        appearance="admin"
                        required
                        @keyup.enter="submitEditFacility"
                /></FormField>
                <div class="flex justify-end gap-3 pt-2">
                    <AppButton
                        variant="neutral"
                        size="sm"
                        @click="closeEditModal"
                    >
                        Batal
                    </AppButton>
                    <AppButton
                        variant="warning"
                        size="sm"
                        @click="submitEditFacility"
                    >
                        Simpan Perubahan
                    </AppButton>
                </div>
            </div>
        </AppModal>
        <ConfirmModal
            v-if="facilityToDelete"
            title="Hapus Fasilitas"
            :message="`Fasilitas ${facilityToDelete.name} akan dihapus. Tindakan ini tidak dapat dibatalkan.`"
            confirm-label="Hapus"
            @close="closeDeleteModal"
            @confirm="deleteFacility"
        />
    </div>
</template>
