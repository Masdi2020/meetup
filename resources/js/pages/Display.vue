<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ActionIconButton from '@/components/atoms/ActionIconButton.vue';
import type { DisplayRoom } from '@/types/room';

const props = defineProps<{
    rooms: DisplayRoom[];
}>();

const openRoomBanner = (room: DisplayRoom) => {
    if (room.has_display) {
        router.visit(`/display/${room.id}`);
    }
};
</script>

<template>
    <div class="app-page">
        <!-- Header -->
        <div class="page-header page-heading">
            <h1 class="page-title">Ruangan</h1>

            <p class="text-gray-500">Lihat daftar ruangan yang tersedia.</p>
        </div>

        <!-- Total Ruangan -->
        <div class="ui-card ui-card-body">
            <p class="text-sm text-gray-500">Total Ruangan</p>

            <h2 class="mt-2 text-3xl font-bold">
                {{ props.rooms.length }}
            </h2>
        </div>

        <!-- Room List -->
        <div class="ui-card overflow-hidden">
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
                    <ActionIconButton
                        action="detail"
                        :disabled="!room.has_display"
                        :label="
                            room.has_display
                                ? 'Lihat banner rapat'
                                : 'Banner tidak tersedia'
                        "
                        class="ml-4"
                        @click="openRoomBanner(room)"
                    />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="px-5 py-12 text-center text-gray-500">
                Belum ada ruangan.
            </div>
        </div>
    </div>
</template>
