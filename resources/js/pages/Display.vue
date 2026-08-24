<script setup lang="ts">
import { Eye, X } from '@lucide/vue';
import { ref } from 'vue';
interface Room {
    id: number;
    name: string;
    location: string;
    capacity: number;
    is_available: boolean;
    facilities: string[];
}

const props = defineProps<{
    rooms: Room[];
}>();

const showDetailModal = ref(false);
const selectedRoom = ref<Room | null>(null);

function openDetailModal(room: Room) {
    selectedRoom.value = room;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedRoom.value = null;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold">Ruangan</h1>

            <p class="text-gray-500">Lihat daftar ruangan yang tersedia.</p>
        </div>

        <!-- Total Ruangan -->
        <div class="rounded-xl bg-white p-5 shadow">
            <p class="text-sm text-gray-500">Total Ruangan</p>

            <h2 class="mt-2 text-3xl font-bold">
                {{ props.rooms.length }}
            </h2>
        </div>

        <!-- Room List -->
        <div class="overflow-hidden rounded-xl bg-white shadow">
            <div v-if="props.rooms.length" class="divide-y">
                <div
                    v-for="room in props.rooms"
                    :key="room.id"
                    class="flex items-center justify-between px-5 py-4 transition hover:bg-gray-50"
                >
                    <!-- Room Information -->
                    <div class="min-w-0">
                        <h3 class="font-semibold text-gray-900">
                            {{ room.name }}
                        </h3>

                        <div
                            class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500"
                        >
                            <span>
                                {{ room.location }}
                            </span>

                            <span> {{ room.capacity }} orang </span>

                            <span
                                :class="
                                    room.is_available
                                        ? 'text-emerald-600'
                                        : 'text-gray-500'
                                "
                            >
                                {{
                                    room.is_available
                                        ? 'Tersedia'
                                        : 'Tidak tersedia'
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Detail Button -->
                    <button
                        type="button"
                        @click="openDetailModal(room)"
                        class="ml-4 rounded-lg p-2.5 text-gray-500 transition hover:bg-blue-50 hover:text-blue-600"
                        title="Lihat detail"
                    >
                        <Eye class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="px-5 py-12 text-center text-gray-500">
                Belum ada ruangan.
            </div>
        </div>

        <!-- Detail Modal -->
        <div
            v-if="showDetailModal && selectedRoom"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="closeDetailModal"
        >
            <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
                <!-- Modal Header -->
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">
                        Detail Ruangan
                    </h2>

                    <button
                        type="button"
                        @click="closeDetailModal"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Room Detail -->
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Nama Ruangan
                        </p>

                        <p class="mt-1 font-semibold text-gray-900">
                            {{ selectedRoom.name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Lokasi</p>

                        <p class="mt-1 text-gray-900">
                            {{ selectedRoom.location }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Kapasitas
                        </p>

                        <p class="mt-1 text-gray-900">
                            {{ selectedRoom.capacity }} orang
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Status</p>

                        <span
                            :class="
                                selectedRoom.is_available
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-gray-100 text-gray-600'
                            "
                            class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                        >
                            {{
                                selectedRoom.is_available
                                    ? 'Tersedia'
                                    : 'Tidak tersedia'
                            }}
                        </span>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Fasilitas
                        </p>

                        <div
                            v-if="selectedRoom.facilities.length"
                            class="mt-2 flex flex-wrap gap-2"
                        >
                            <span
                                v-for="facility in selectedRoom.facilities"
                                :key="facility"
                                class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                            >
                                {{ facility }}
                            </span>
                        </div>

                        <p v-else class="mt-1 text-sm text-gray-400">
                            Tidak ada fasilitas.
                        </p>
                    </div>
                </div>

                <!-- Close -->
                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        @click="closeDetailModal"
                        class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-100"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
