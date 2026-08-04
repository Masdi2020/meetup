<script setup lang="ts">
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

interface Room {
    id: number;
    name: string;
    floor: number;
    capacity: number;
    calendar_url: string | null;
    facilities: string[];
}

const props = defineProps<{
    rooms: Room[];
    filters: {
        search: string;
    };
}>();

const search = ref(props.filters.search ?? '');

const rooms = computed(() => props.rooms);

const filteredRooms = computed(() =>
    rooms.value.filter((room) =>
        room.name.toLowerCase().includes(search.value.toLowerCase()),
    ),
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

            <div class="rouded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Total Kapasitas</p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{
                        rooms.reduce((total, room) => total + room.capacity, 0)
                    }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Total Fasilitas</p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{
                        rooms.reduce(
                            (total, room) => total + room.facilities.length,
                            0,
                        )
                    }}
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
            </div>
        </div>

        <!-- Table -->

        <div class="overflow-hidden rounded-xl bg-white shadow">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr class="text-left text-sm">
                        <th class="px-5 py-4">Nama</th>

                        <th class="px-5 py-4">Lantai</th>

                        <th class="px-5 py-4">Kapasitas</th>

                        <th class="px-5 py-4">Fasilitas</th>

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
