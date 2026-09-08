<script setup lang="ts">
import RoomSummaryCard from '@/components/molecules/RoomSummaryCard.vue';
import DetailModal from '@/components/organisms/DetailModal.vue';
import type { RoomDetails } from '@/types/room';

defineProps<{
    room: RoomDetails;
}>();

const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <DetailModal
        :title="`Detail ${room.name}`"
        max-width="lg"
        @close="emit('close')"
    >
        <RoomSummaryCard :room="room" />
    </DetailModal>
</template>
