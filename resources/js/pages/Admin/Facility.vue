<script setup lang="ts">
import { computed, ref } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";

defineOptions({
    layout: AdminLayout,
});

interface Facility {
    id: number;
    name: string;
    description: string;
    rooms: number;
    active: boolean;
}

const search = ref("");
const statusFilter = ref("");

const facilities = ref<Facility[]>([
    {
        id: 1,
        name: "Proyektor",
        description: "LCD Projector",
        rooms: 8,
        active: true,
    },
    {
        id: 2,
        name: "AC",
        description: "Pendingin Ruangan",
        rooms: 12,
        active: true,
    },
    {
        id: 3,
        name: "Whiteboard",
        description: "Papan Tulis",
        rooms: 5,
        active: true,
    },
    {
        id: 4,
        name: "Microphone",
        description: "Wireless Microphone",
        rooms: 2,
        active: false,
    },
]);

const filteredFacilities = computed(() =>
    facilities.value.filter((facility) => {
        const keyword =
            facility.name.toLowerCase().includes(search.value.toLowerCase()) ||
            facility.description
                .toLowerCase()
                .includes(search.value.toLowerCase());

        const status =
            !statusFilter.value ||
            (statusFilter.value === "active" && facility.active) ||
            (statusFilter.value === "inactive" && !facility.active);

        return keyword && status;
    }),
);
</script>

<template>
    <div class="space-y-6">

        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-3xl font-bold">
                    Fasilitas
                </h1>

                <p class="text-gray-500">
                    Kelola fasilitas yang tersedia pada ruangan.
                </p>
            </div>

            <button
                class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
            >
                + Tambah Fasilitas
            </button>

        </div>

        <!-- Statistik -->

        <div class="grid gap-4 md:grid-cols-3">

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    Total Fasilitas
                </p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ facilities.length }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    Aktif
                </p>

                <h2 class="mt-2 text-3xl font-bold text-green-600">
                    {{ facilities.filter(f => f.active).length }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    Nonaktif
                </p>

                <h2 class="mt-2 text-3xl font-bold text-red-600">
                    {{ facilities.filter(f => !f.active).length }}
                </h2>
            </div>

        </div>

        <!-- Filter -->

        <div class="rounded-xl bg-white p-5 shadow">

            <div class="grid gap-4 md:grid-cols-2">

                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari fasilitas..."
                    class="rounded-lg border px-4 py-2"
                >

                <select
                    v-model="statusFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>

            </div>

        </div>

        <!-- Table -->

        <div class="overflow-hidden rounded-xl bg-white shadow">

            <table class="min-w-full">

                <thead class="bg-gray-100">

                    <tr class="text-left text-sm">

                        <th class="px-5 py-4">Nama</th>
                        <th class="px-5 py-4">Deskripsi</th>
                        <th class="px-5 py-4">Digunakan</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>

                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="facility in filteredFacilities"
                        :key="facility.id"
                        class="border-t hover:bg-gray-50"
                    >

                        <td class="px-5 py-4 font-medium">
                            {{ facility.name }}
                        </td>

                        <td class="px-5 py-4">
                            {{ facility.description }}
                        </td>

                        <td class="px-5 py-4">
                            {{ facility.rooms }} Ruangan
                        </td>

                        <td class="px-5 py-4">

                            <span
                                :class="facility.active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'"
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                            >
                                {{ facility.active ? "Aktif" : "Nonaktif" }}
                            </span>

                        </td>

                        <td class="px-5 py-4">

                            <div class="flex justify-end gap-2">

                                <button
                                    class="rounded-lg border px-3 py-2 hover:bg-gray-100"
                                >
                                    Detail
                                </button>

                                <button
                                    class="rounded-lg bg-yellow-500 px-3 py-2 text-white hover:bg-yellow-600"
                                >
                                    Edit
                                </button>

                                <button
                                    class="rounded-lg bg-red-600 px-3 py-2 text-white hover:bg-red-700"
                                >
                                    Hapus
                                </button>

                            </div>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>
</template>
