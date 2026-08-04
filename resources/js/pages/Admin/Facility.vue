<script setup lang="ts">
import { computed, ref } from 'vue';
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

const search = ref('');

const filteredFacilities = computed(() =>
    props.facilities.filter((facility) =>
        facility.name.toLowerCase().includes(search.value.toLowerCase()),
    ),
);

const totalUsage = computed(() =>
    props.facilities.reduce(
        (total, facility) => total + facility.rooms_count,
        0,
    ),
);
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
            >
                + Tambah Fasilitas
            </button>
        </div>

        <!-- Statistik -->
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Total Fasilitas</p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ props.facilities.length }}
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
                                >
                                    Detail
                                </button>

                                <button
                                    class="rounded-lg bg-yellow-500 px-3 py-2 text-white transition hover:bg-yellow-600"
                                >
                                    Edit
                                </button>

                                <button
                                    class="rounded-lg bg-red-600 px-3 py-2 text-white transition hover:bg-red-700"
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
    </div>
</template>
