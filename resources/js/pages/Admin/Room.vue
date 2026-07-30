<script setup lang="ts">
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

interface Room {
    id: number;
    name: string;
    building: string;
    floor: number;
    capacity: number;
    facilities: string[];
    active: boolean;
}

const search = ref('');
const statusFilter = ref('');

const rooms = ref<Room[]>([
    {
        id: 1,
        name: 'Ruang Rapat A',
        building: 'Gedung Utama',
        floor: 1,
        capacity: 20,
        facilities: ['Proyektor', 'AC', 'TV'],
        active: true,
    },
    {
        id: 2,
        name: 'Ruang Rapat B',
        building: 'Gedung Utama',
        floor: 2,
        capacity: 10,
        facilities: ['AC', 'Whiteboard'],
        active: true,
    },
    {
        id: 3,
        name: 'Lab Komputer',
        building: 'Gedung B',
        floor: 1,
        capacity: 30,
        facilities: ['Komputer', 'Proyektor', 'AC'],
        active: false,
    },
]);

const filteredRooms = computed(() =>
    rooms.value.filter((room) => {
        const keyword =
            room.name.toLowerCase().includes(search.value.toLowerCase()) ||
            room.building.toLowerCase().includes(search.value.toLowerCase());

        const status =
            !statusFilter.value ||
            (statusFilter.value === 'active' && room.active) ||
            (statusFilter.value === 'inactive' && !room.active);

        return keyword && status;
    }),
);
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Ruangan</h1>

                <p class="text-gray-500">
                    Kelola seluruh ruangan yang tersedia.
                </p>
            </div>

            <button
                class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
            >
                + Tambah Ruangan
            </button>
        </div>

        <!-- Statistik -->

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Total Ruangan</p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ rooms.length }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Aktif</p>

                <h2 class="mt-2 text-3xl font-bold text-green-600">
                    {{ rooms.filter((r) => r.active).length }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Nonaktif</p>

                <h2 class="mt-2 text-3xl font-bold text-red-600">
                    {{ rooms.filter((r) => !r.active).length }}
                </h2>
            </div>
        </div>

        <!-- Filter -->

        <div class="rounded-xl bg-white p-5 shadow">
            <div class="grid gap-4 md:grid-cols-2">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari ruangan..."
                    class="rounded-lg border px-4 py-2"
                />

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

                        <th class="px-5 py-4">Gedung</th>

                        <th class="px-5 py-4">Lantai</th>

                        <th class="px-5 py-4">Kapasitas</th>

                        <th class="px-5 py-4">Fasilitas</th>

                        <th class="px-5 py-4">Status</th>

                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="room in filteredRooms"
                        :key="room.id"
                        class="border-t hover:bg-gray-50"
                    >
                        <td class="px-5 py-4 font-medium">
                            {{ room.name }}
                        </td>

                        <td class="px-5 py-4">
                            {{ room.building }}
                        </td>

                        <td class="px-5 py-4">
                            {{ room.floor }}
                        </td>

                        <td class="px-5 py-4">{{ room.capacity }} Orang</td>

                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="facility in room.facilities"
                                    :key="facility"
                                    class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700"
                                >
                                    {{ facility }}
                                </span>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <span
                                :class="
                                    room.active
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-red-100 text-red-700'
                                "
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                            >
                                {{ room.active ? 'Aktif' : 'Nonaktif' }}
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
