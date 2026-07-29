<script setup lang="ts">
import { ref } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";

defineOptions({
    layout: AdminLayout,
});

const settings = ref({
    appName: "Meeting Room",

    organization: "Diskominfo",

    allowSameDayBooking: true,

    maxBookingHour: 4,

    maxBookingDay: 30,

    openTime: "07:30",

    closeTime: "17:30",

    emailNotification: true,

    dashboardNotification: true,

    sessionTimeout: 60,
});

function save() {
    alert("Simpan konfigurasi");
}
</script>

<template>

<div class="space-y-6">

    <div>

        <h1 class="text-3xl font-bold">
            Pengaturan
        </h1>

        <p class="text-gray-500">
            Konfigurasi aplikasi peminjaman ruangan.
        </p>

    </div>

    <!-- Informasi -->

    <div class="bg-white rounded-xl shadow">

        <div class="border-b px-6 py-4">

            <h2 class="font-semibold text-lg">
                Informasi Sistem
            </h2>

        </div>

        <div class="p-6 grid gap-5">

            <div>

                <label class="block mb-2 font-medium">
                    Nama Aplikasi
                </label>

                <input
                    v-model="settings.appName"
                    class="w-full rounded-lg border px-4 py-2"
                >

            </div>

            <div>

                <label class="block mb-2 font-medium">
                    Nama Instansi
                </label>

                <input
                    v-model="settings.organization"
                    class="w-full rounded-lg border px-4 py-2"
                >

            </div>

            <div>

                <label class="block mb-2 font-medium">
                    Logo
                </label>

                <input
                    type="file"
                    class="w-full rounded-lg border p-2"
                >

            </div>

        </div>

    </div>

    <!-- Booking -->

    <div class="bg-white rounded-xl shadow">

        <div class="border-b px-6 py-4">

            <h2 class="font-semibold text-lg">
                Pengaturan Booking
            </h2>

        </div>

        <div class="p-6 space-y-5">

            <label class="flex items-center gap-3">

                <input
                    v-model="settings.allowSameDayBooking"
                    type="checkbox"
                >

                Izinkan booking pada hari yang sama

            </label>

            <div>

                <label class="block mb-2 font-medium">
                    Maksimal Lama Booking (Jam)
                </label>

                <input
                    v-model="settings.maxBookingHour"
                    type="number"
                    class="w-full rounded-lg border px-4 py-2"
                >

            </div>

            <div>

                <label class="block mb-2 font-medium">
                    Maksimal Hari Sebelum Booking
                </label>

                <input
                    v-model="settings.maxBookingDay"
                    type="number"
                    class="w-full rounded-lg border px-4 py-2"
                >

            </div>

        </div>

    </div>

    <!-- Jam Operasional -->

    <div class="bg-white rounded-xl shadow">

        <div class="border-b px-6 py-4">

            <h2 class="font-semibold text-lg">
                Jam Operasional
            </h2>

        </div>

        <div class="grid md:grid-cols-2 gap-5 p-6">

            <div>

                <label class="block mb-2 font-medium">
                    Jam Mulai
                </label>

                <input
                    v-model="settings.openTime"
                    type="time"
                    class="w-full rounded-lg border px-4 py-2"
                >

            </div>

            <div>

                <label class="block mb-2 font-medium">
                    Jam Selesai
                </label>

                <input
                    v-model="settings.closeTime"
                    type="time"
                    class="w-full rounded-lg border px-4 py-2"
                >

            </div>

        </div>

    </div>

    <!-- Notifikasi -->

    <div class="bg-white rounded-xl shadow">

        <div class="border-b px-6 py-4">

            <h2 class="font-semibold text-lg">
                Notifikasi
            </h2>

        </div>

        <div class="space-y-4 p-6">

            <label class="flex items-center gap-3">

                <input
                    v-model="settings.emailNotification"
                    type="checkbox"
                >

                Aktifkan Email

            </label>

            <label class="flex items-center gap-3">

                <input
                    v-model="settings.dashboardNotification"
                    type="checkbox"
                >

                Aktifkan Notifikasi Dashboard

            </label>

        </div>

    </div>

    <!-- Keamanan -->

    <div class="bg-white rounded-xl shadow">

        <div class="border-b px-6 py-4">

            <h2 class="font-semibold text-lg">
                Keamanan
            </h2>

        </div>

        <div class="p-6">

            <label class="block mb-2 font-medium">
                Session Timeout (Menit)
            </label>

            <input
                v-model="settings.sessionTimeout"
                type="number"
                class="w-full rounded-lg border px-4 py-2"
            >

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
