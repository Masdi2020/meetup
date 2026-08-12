<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

interface Facility {
    id: number;
    name: string;
    rooms_count: number;
}

const props = defineProps<{
    facilities: Facility[];
}>();

const facilities = ref<Facility[]>([...props.facilities]);
const search = ref('');
const showAddModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const selectedFacility = ref<Facility | null>(null);
const addFacilityForm = useForm({ name: '' });
const editFacilityForm = useForm({ name: '' });

watch(
    () => props.facilities,
    (value) => {
        facilities.value = [...value];
    },
    { immediate: true },
);

const filteredFacilities = computed(() =>
    facilities.value.filter((facility) =>
        facility.name.toLowerCase().includes(search.value.toLowerCase()),
    ),
);

const totalUsage = computed(() =>
    facilities.value.reduce((total, facility) => total + facility.rooms_count, 0),
);

function openAddModal() {
    addFacilityForm.reset();
    showAddModal.value = true;
}

function closeAddModal() {
    showAddModal.value = false;
    addFacilityForm.reset();
}

function submitAddFacility() {
    if (!addFacilityForm.name.trim()) {
        return;
    }

    addFacilityForm.post('/admin/facilities', {
        preserveScroll: true,
        onSuccess: () => {
            closeAddModal();
        },
    });
}

function openDetailModal(facility: Facility) {
    selectedFacility.value = facility;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedFacility.value = null;
}

function openEditModal(facility: Facility) {
    selectedFacility.value = facility;
    editFacilityForm.name = facility.name;
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
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

    editFacilityForm.put(`/admin/facilities/${selectedFacility.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Fasilitas</h1>

                <p class="text-gray-500">
                    Kelola fasilitas yang tersedia pada ruangan.
                </p>
            </div>

            <button
                class="rounded-lg bg-blue-600 px-5 py-3 text-white transition hover:bg-blue-700"
                @click="openAddModal"
            >
                + Tambah Fasilitas
            </button>
        </div>

        <!-- Statistik -->
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Total Fasilitas</p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ facilities.length }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Digunakan di Ruangan</p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ totalUsage }}
                </h2>
            </div>
        </div>

        <!-- Filter -->
        <div class="rounded-xl bg-white p-5 shadow">
            <input
                v-model="search"
                type="text"
                placeholder="Cari fasilitas..."
                class="w-full rounded-lg border px-4 py-2 outline-none focus:border-blue-500"
            />
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl bg-white shadow">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr class="text-left text-sm font-semibold">
                        <th class="px-5 py-4">Nama</th>

                        <th class="px-5 py-4">Digunakan</th>

                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="facility in filteredFacilities"
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
                                <button
                                    class="rounded-lg border px-3 py-2 transition hover:bg-gray-100"
                                    @click="openDetailModal(facility)"
                                >
                                    Detail
                                </button>

                                <button
                                    class="rounded-lg bg-yellow-500 px-3 py-2 text-white transition hover:bg-yellow-600"
                                    @click="openEditModal(facility)"
                                >
                                    Edit
                                </button>

                                <button
                                    class="cursor-not-allowed rounded-lg bg-red-600 px-3 py-2 text-white opacity-50"
                                    title="Hapus akan dibuat nanti"
                                    disabled
                                >
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="filteredFacilities.length === 0">
                        <td colspan="3" class="py-10 text-center text-gray-500">
                            Tidak ada fasilitas yang ditemukan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="showAddModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="closeAddModal"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold">Tambah Fasilitas</h2>

                    <button
                        class="text-xl text-gray-500 hover:text-gray-700"
                        @click="closeAddModal"
                    >
                        ×
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Nama fasilitas
                        </label>

                        <input
                            v-model="addFacilityForm.name"
                            type="text"
                            placeholder="Masukkan nama fasilitas"
                            class="w-full rounded-lg border px-4 py-2 outline-none focus:border-blue-500"
                            @keyup.enter="submitAddFacility"
                        />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            class="rounded-lg border px-4 py-2 text-gray-700 hover:bg-gray-100"
                            @click="closeAddModal"
                        >
                            Batal
                        </button>

                        <button
                            class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                            @click="submitAddFacility"
                        >
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showDetailModal && selectedFacility"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="closeDetailModal"
        >
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold">Detail Fasilitas</h2>

                    <button
                        class="text-xl text-gray-500 hover:text-gray-700"
                        @click="closeDetailModal"
                    >
                        ×
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Nama fasilitas</p>

                        <p class="mt-1 text-lg font-semibold">
                            {{ selectedFacility.name }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Dipakai di ruangan</p>

                        <p class="mt-1 text-lg font-semibold">
                            {{ selectedFacility.rooms_count }} Ruangan
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        class="rounded-lg bg-gray-800 px-4 py-2 text-white hover:bg-gray-900"
                        @click="closeDetailModal"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="showEditModal && selectedFacility"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            @click.self="closeEditModal"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold">Edit Fasilitas</h2>

                    <button
                        class="text-xl text-gray-500 hover:text-gray-700"
                        @click="closeEditModal"
                    >
                        ×
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Nama fasilitas
                        </label>

                        <input
                            v-model="editFacilityForm.name"
                            type="text"
                            class="w-full rounded-lg border px-4 py-2 outline-none focus:border-blue-500"
                            @keyup.enter="submitEditFacility"
                        />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            class="rounded-lg border px-4 py-2 text-gray-700 hover:bg-gray-100"
                            @click="closeEditModal"
                        >
                            Batal
                        </button>

                        <button
                            class="rounded-lg bg-yellow-500 px-4 py-2 text-white hover:bg-yellow-600"
                            @click="submitEditFacility"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
