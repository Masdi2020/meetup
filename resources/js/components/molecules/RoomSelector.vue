<script setup lang="ts">
import { Info } from '@lucide/vue';
import type { RoomDetails } from '@/types/room';

defineProps<{
    rooms: RoomDetails[];
    selectAllLabel?: string;
}>();

const selectedRoomIds = defineModel<number[]>({ required: true });
const emit = defineEmits<{ detail: [room: RoomDetails] }>();
</script>

<template>
    <fieldset class="toolbar">
        <legend class="room-filter-heading">
            <span>Pilih Ruangan</span>
            <button
                type="button"
                class="text-sm font-medium text-blue-600 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!rooms.length"
                @click="
                    selectedRoomIds =
                        selectedRoomIds.length === rooms.length
                            ? []
                            : rooms.map((room) => room.id)
                "
            >
                {{
                    selectedRoomIds.length === rooms.length
                        ? 'Batalkan semua'
                        : (selectAllLabel ?? 'Pilih semua')
                }}
            </button>
        </legend>
        <div class="room-options">
            <div v-for="room in rooms" :key="room.id" class="room-option">
                <label
                    class="flex min-w-0 flex-1 cursor-pointer items-center gap-3"
                >
                    <input
                        v-model="selectedRoomIds"
                        type="checkbox"
                        :value="room.id"
                    />
                    <span class="min-w-0">
                        <span class="block font-medium text-slate-900">{{
                            room.name
                        }}</span>
                        <span class="block text-xs text-slate-500">
                            {{ room.capacity }} orang · {{ room.location }}
                        </span>
                    </span>
                </label>
                <button
                    type="button"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-blue-600"
                    :aria-label="`Lihat detail ${room.name}`"
                    :title="`Lihat detail ${room.name}`"
                    @click="emit('detail', room)"
                >
                    <Info :size="17" aria-hidden="true" />
                </button>
            </div>
        </div>
    </fieldset>
</template>

<style scoped>
.toolbar {
    min-width: 0;
    margin: 0;
    padding: 0;
    border: 0;
}

.toolbar legend {
    margin-bottom: 8px;
}

.room-filter-heading {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.room-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.room-option {
    display: flex;
    min-height: 44px;
    flex: 1 1 240px;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--ui-border);
    border-radius: 8px;
    background: white;
    padding: 8px 12px;
}

.room-option input {
    width: 18px;
    height: 18px;
    accent-color: #2563eb;
}
</style>
