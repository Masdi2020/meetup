<script setup lang="ts">
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

const settings = ref({
    appName: 'Meeting Room',

    organization: 'Diskominfo',

    allowSameDayBooking: true,

    maxBookingHour: 4,

    maxBookingDay: 30,

    openTime: '07:30',

    closeTime: '17:30',

    emailNotification: true,

    dashboardNotification: true,

    sessionTimeout: 60,
});

function save() {
    alert('Simpan konfigurasi');
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-bold">Pengaturan</h1>

            <p class="text-gray-500">
                Konfigurasi aplikasi peminjaman ruangan.
            </p>
        </div>

        <!-- Informasi -->

        <div class="rounded-xl bg-white shadow">
            <div class="border-b px-6 py-4">
                <h2 class="text-lg font-semibold">Informasi Sistem</h2>
            </div>

            <div class="grid gap-5 p-6">
                <div>
                    <label class="mb-2 block font-medium">
                        Nama Aplikasi
                    </label>

                    <input
                        v-model="settings.appName"
                        class="w-full rounded-lg border px-4 py-2"
                    />
                </div>

                <div>
                    <label class="mb-2 block font-medium">
                        Nama Instansi
                    </label>

                    <input
                        v-model="settings.organization"
                        class="w-full rounded-lg border px-4 py-2"
                    />
                </div>

                <div>
                    <label class="mb-2 block font-medium"> Logo </label>

                    <input type="file" class="w-full rounded-lg border p-2" />
                </div>
            </div>
        </div>

        <!-- Keamanan -->

        <div class="rounded-xl bg-white shadow">
            <div class="border-b px-6 py-4">
                <h2 class="text-lg font-semibold">Keamanan</h2>
            </div>

            <div class="p-6">
                <label class="mb-2 block font-medium">
                    Session Timeout (Menit)
                </label>

                <input
                    v-model="settings.sessionTimeout"
                    type="number"
                    class="w-full rounded-lg border px-4 py-2"
                />
            </div>
        </div>

        <div class="flex justify-end">
            <button
                @click="save"
                class="rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700"
            >
                Simpan Perubahan
            </button>
        </div>
    </div>
</template>
