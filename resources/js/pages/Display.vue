<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
interface Room {
    id: number;
    name: string;
    location: string;
    capacity: number;
    is_available: boolean;
    has_display: boolean;
    facilities: string[];
}

const props = defineProps<{
    rooms: Room[];
}>();

const openRoomBanner = (room: Room) => {
    if (room.has_display) {
        router.visit(`/display/${room.id}`);
    }
};
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
                        :disabled="!room.has_display"
                        :class="[
                            'ml-4 rounded-lg p-2.5 transition',
                            room.has_display
                                ? 'text-gray-500 hover:bg-blue-50 hover:text-blue-600'
                                : 'cursor-not-allowed text-gray-300',
                        ]"
                        :title="
                            room.has_display
                                ? 'Lihat banner rapat'
                                : 'Banner tidak tersedia'
                        "
                        @click="openRoomBanner(room)"
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
    </div>
</template>
