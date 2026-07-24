<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed } from "vue";

interface Facility {
    id: number;
    name: string;
}

interface Room {
    id: number;
    name: string;
    capacity: number;
    floor: string;
    calendar_url: string;
    facilities: Facility[];
}

const { rooms } = defineProps<{
    rooms: Room[];
}>();

const selectedRoomId = ref<number>(rooms[0]?.id ?? 1);

const selectedRoom = computed(() =>
  rooms.find(room => room.id === selectedRoomId.value)
);

function booking (roomId: number) {
    router.get(`/booking/${roomId}`);
}
</script>

<template>
  <div class="availability">

    <h2>Ketersediaan Ruangan</h2>

    <div class="page-card">

    <div class="toolbar">
      <label>Pilih Ruangan</label>

      <select v-model="selectedRoomId">
        <option
          v-for="room in rooms"
          :key="room.id"
          :value="room.id"
        >
          {{ room.name }}
        </option>
      </select>
    </div>

    <div class="room-card" v-if="selectedRoom">

      <div class="room-image">
        <img
            v-if="selectedRoom.image"
            :src="`/storage/${selectedRoom.image}`"
            :alt="selectedRoom.name"
        >
        <span v-else>🖼️</span>
      </div>

      <div class="room-info">

        <h3>{{ selectedRoom.name }}</h3>

        <div class="meta">
          <span>👥 {{ selectedRoom.capacity }} orang</span>
          <span>📍 {{ selectedRoom.floor }}</span>
          <span>
            🎥
            <template
                v-for="(facility, index) in selectedRoom.facilities"
                :key="facility.id"
            >
                {{ facility.name }}<span v-if="index < selectedRoom.facilities.length - 1">, </span>
            </template>
          </span>
        </div>

        <button class="booking" @click="booking(selectedRoom.id)">
          Booking
        </button>

      </div>

    </div>

    <div class="calendar-card" v-if="selectedRoom">
      <iframe
        class="calendar-frame"
        :src="`${selectedRoom.calendar_url}&showPrint=0&showTz=0`"
        frameborder="0"
        scrolling="no"
      ></iframe>
    </div>

    </div>

  </div>
</template>

<style>
.availability {
  width: 100%;
  margin: 0;
  padding: 30px;
}

.page-card {
  background: #cfe2ff;
  border-radius: 10px;
  padding: 28px;
  max-width: 1100px;
  margin: 0; /* align left with heading */
}

h2 {
  font-size: 24px;
  margin-bottom: 15px;
  color: #173b7a;
  border-bottom: 2px solid #d9d9d9;
  width: fit-content;
}

.toolbar{
    margin:20px 0;
    display:flex;
    flex-direction:column;
    gap:8px;
}

.toolbar select{
    width:300px;
    padding:10px;
    border-radius:8px;
    border:1px solid #ddd;
}

.room-card{
    display:flex;
    gap:20px;
    padding:20px;
    background:#fff;
    border-radius:12px;
    border:1px solid #e5e7eb;
    margin-bottom:20px;
}

.room-image{
    width:120px;
    height:120px;
    background:#f5f5f5;
    display:flex;
    justify-content:center;
    align-items:center;
    border-radius:10px;
    font-size:42px;
}

.room-info{
    flex:1;
}

.room-info h3{
    margin-bottom:10px;
}

.meta{
    display:flex;
    flex-wrap:wrap;
    gap:18px;
    color:#666;
    margin-bottom:20px;
}

.booking{
    padding:10px 20px;
    background:#2563eb;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.calendar-card{
    height:700px;
    border:1px solid #ddd;
    border-radius:12px;
    overflow:hidden;
}

.calendar-frame{
    width:100%;
    height:100%;
    border:none;
}
</style>
