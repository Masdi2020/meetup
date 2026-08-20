<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
interface FacilityOption {
    id: number;
    name: string;
}

interface Room {
    id: number;
    name: string;
    location: string;
    capacity: number;
    is_available: boolean;
    facilities: string[];
    facility_ids: number[];
}

const props = defineProps<{
    rooms: Room[];
    facilities?: FacilityOption[];
    filters: {
        search: string;
    };
}>();

const search = ref(props.filters.search ?? '');
const showDetailModal = ref(false);
const showFormModal = ref(false);
const isEditing = ref(false);
const selectedRoom = ref<Room | null>(null);

const roomForm = useForm({
    name: '',
    capacity: 1,
    location: '',
    is_available: true,
    facilities: [] as number[],
});

const rooms = computed(() => props.rooms);

const filteredRooms = computed(() =>
    rooms.value.filter((room) => {
        const term = search.value.toLowerCase();

        return (
            room.name.toLowerCase().includes(term) ||
            room.location.toLowerCase().includes(term)
        );
    }),
);

const totalCapacity = computed(() =>
    rooms.value.reduce((total, room) => total + room.capacity, 0),
);

const totalFacilities = computed(() =>
    rooms.value.reduce((total, room) => total + room.facilities.length, 0),
);

function openCreateModal() {
    isEditing.value = false;
    selectedRoom.value = null;
    roomForm.reset();
    roomForm.capacity = 1;
    roomForm.is_available = true;
    roomForm.facilities = [];
    showFormModal.value = true;
}

function openEditModal(room: Room) {
    isEditing.value = true;
    selectedRoom.value = room;
    roomForm.name = room.name;
    roomForm.capacity = room.capacity;
    roomForm.location = room.location;
    roomForm.is_available = room.is_available;
    roomForm.facilities = [...room.facility_ids];
    showFormModal.value = true;
}

function closeFormModal() {
    showFormModal.value = false;
    selectedRoom.value = null;
    roomForm.reset();
    roomForm.capacity = 1;
    roomForm.is_available = true;
    roomForm.facilities = [];
}

function openDetailModal(room: Room) {
    selectedRoom.value = room;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedRoom.value = null;
}

function submitRoomForm() {
    if (isEditing.value && selectedRoom.value) {
        roomForm.put(`/admin/rooms/${selectedRoom.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });

        return;
    }

    roomForm.post('/admin/rooms', {
        preserveScroll: true,
        onSuccess: () => closeFormModal(),
    });
}

function toggleAvailability(room: Room) {
    if (
        !confirm(
            `${room.is_available ? 'Nonaktifkan' : 'Aktifkan'} ruangan ${room.name}?`,
        )
    ) {
        return;
    }

    router.patch(
        `/admin/rooms/${room.id}/toggle-availability`,
        {},
        {
            preserveScroll: true,
        },
    );
}
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
                @click="openCreateModal"
                class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
            >
                + Tambah Ruangan
            </button>
        </div>

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
                    {{ totalCapacity }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Total Fasilitas</p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ totalFacilities }}
                </h2>
            </div>
        </div>

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

        <div class="overflow-hidden rounded-xl bg-white shadow">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr class="text-left text-sm">
                        <th class="px-5 py-4">Nama</th>
                        <th class="px-5 py-4">Lokasi</th>
                        <th class="px-5 py-4">Kapasitas</th>
                        <th class="px-5 py-4">Status</th>
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
                            {{ room.location }}
                        </td>

                        <td class="px-5 py-4">{{ room.capacity }} Orang</td>

                        <td class="px-5 py-4">
                            <span
                                :class="
                                    room.is_available
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-gray-200 text-gray-700'
                                "
                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                            >
                                {{ room.is_available ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>

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
                                    @click="openDetailModal(room)"
                                    class="rounded-lg border px-3 py-2 hover:bg-gray-100"
                                >
                                    Detail
                                </button>

                                <button
                                    @click="openEditModal(room)"
                                    class="rounded-lg bg-yellow-500 px-3 py-2 text-white hover:bg-yellow-600"
                                >
                                    Edit
                                </button>

                                <button
                                    @click="toggleAvailability(room)"
                                    :class="
                                        room.is_available
                                            ? 'bg-gray-600 hover:bg-gray-700'
                                            : 'bg-emerald-600 hover:bg-emerald-700'
                                    "
                                    class="rounded-lg px-3 py-2 text-white"
                                >
                                    {{
                                        room.is_available
                                            ? 'Nonaktifkan'
                                            : 'Aktifkan'
                                    }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="showDetailModal && selectedRoom"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        >
            <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-2xl font-bold">Detail Ruangan</h2>

                    <button
                        @click="closeDetailModal"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-3 text-sm text-gray-700">
                    <div>
                        <p class="font-semibold text-gray-500">Nama</p>
                        <p>{{ selectedRoom.name }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-500">Lokasi</p>
                        <p>{{ selectedRoom.location }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-500">Kapasitas</p>
                        <p>{{ selectedRoom.capacity }} orang</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-500">Status</p>
                        <p>
                            <span
                                :class="
                                    selectedRoom.is_available
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-gray-200 text-gray-700'
                                "
                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                            >
                                {{
                                    selectedRoom.is_available
                                        ? 'Aktif'
                                        : 'Nonaktif'
                                }}
                            </span>
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-500">Fasilitas</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="facility in selectedRoom.facilities"
                                :key="facility"
                                class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700"
                            >
                                {{ facility }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showFormModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        >
            <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-2xl font-bold">
                        {{ isEditing ? 'Edit Ruangan' : 'Tambah Ruangan' }}
                    </h2>

                    <button
                        @click="closeFormModal"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitRoomForm" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium"
                            >Nama Ruangan</label
                        >
                        <input
                            v-model="roomForm.name"
                            type="text"
                            class="w-full rounded-lg border px-4 py-2"
                            required
                        />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Kapasitas</label
                            >
                            <input
                                v-model.number="roomForm.capacity"
                                type="number"
                                min="1"
                                class="w-full rounded-lg border px-4 py-2"
                                required
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium"
                                >Lokasi</label
                            >
                            <input
                                v-model="roomForm.location"
                                type="text"
                                class="w-full rounded-lg border px-4 py-2"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium"
                            >Fasilitas</label
                        >
                        <div class="grid gap-2 md:grid-cols-2">
                            <label
                                v-for="facility in props.facilities ?? []"
                                :key="facility.id"
                                class="flex items-center gap-2 rounded-lg border p-2"
                            >
                                <input
                                    v-model="roomForm.facilities"
                                    :value="facility.id"
                                    type="checkbox"
                                />
                                <span>{{ facility.name }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label
                            class="flex items-center gap-2 text-sm font-medium"
                        >
                            <input
                                v-model="roomForm.is_available"
                                type="checkbox"
                            />
                            Aktif tersedia untuk booking
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeFormModal"
                            class="rounded-lg border px-4 py-2 hover:bg-gray-100"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                            :disabled="roomForm.processing"
                        >
                            {{
                                isEditing
                                    ? 'Simpan Perubahan'
                                    : 'Tambah Ruangan'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
