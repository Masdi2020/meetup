<script setup lang="ts">
import { MapPin, Users } from '@lucide/vue';
import type { RoomDetails } from '@/types/room';

defineProps<{
    room: RoomDetails;
}>();
</script>

<template>
    <article
        class="flex gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4"
    >
        <div
            class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-200 text-3xl"
        >
            <img
                v-if="room.image_path"
                :src="`/storage/${room.image_path}`"
                :alt="room.name"
                class="h-full w-full object-cover"
            />
            <span v-else aria-hidden="true">🖼️</span>
        </div>
        <div class="min-w-0">
            <h3 class="font-semibold text-slate-900">{{ room.name }}</h3>
            <div
                class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-600"
            >
                <span class="inline-flex items-center gap-1">
                    <Users :size="15" aria-hidden="true" />
                    {{ room.capacity }} orang
                </span>
                <span class="inline-flex items-center gap-1">
                    <MapPin :size="15" aria-hidden="true" />
                    {{ room.location }}
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-600">
                <span class="font-medium text-slate-700">Fasilitas:</span>
                {{
                    room.facilities
                        .map((facility) => facility.name)
                        .join(', ') || '-'
                }}
            </p>
        </div>
    </article>
</template>
