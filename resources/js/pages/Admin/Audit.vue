<script setup lang="ts">
import { computed, ref } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";

defineOptions({
    layout: AdminLayout,
});

interface Audit {
    id: number;
    created_at: string;
    user: string;
    role: string;
    action: string;
    description: string;
    ip: string;
}

const search = ref("");
const roleFilter = ref("");

const audits = ref<Audit[]>([
    {
        id: 1,
        created_at: "29 Jul 2026 08:15",
        user: "Administrator",
        role: "Admin",
        action: "Approve Booking",
        description: "Booking BK-0001 disetujui.",
        ip: "192.168.1.10",
    },
    {
        id: 2,
        created_at: "29 Jul 2026 09:40",
        user: "Dimas",
        role: "User",
        action: "Create Booking",
        description: "Booking BK-0002 dibuat.",
        ip: "192.168.1.25",
    },
    {
        id: 3,
        created_at: "29 Jul 2026 10:30",
        user: "Administrator",
        role: "Admin",
        action: "Update Room",
        description: "Mengubah kapasitas Ruang Rapat A.",
        ip: "192.168.1.10",
    },
]);

const filtered = computed(() =>
    audits.value.filter((audit) => {
        const keyword =
            audit.user.toLowerCase().includes(search.value.toLowerCase()) ||
            audit.action.toLowerCase().includes(search.value.toLowerCase());

        const role =
            !roleFilter.value || audit.role === roleFilter.value;

        return keyword && role;
    }),
);

function badge(role: string) {
    return role === "Admin"
        ? "bg-indigo-100 text-indigo-700"
        : "bg-green-100 text-green-700";
}
</script>

<template>
<div class="space-y-6">

    <div class="flex justify-between items-center">

        <div>

            <h1 class="text-3xl font-bold">
                Audit Log
            </h1>

            <p class="text-gray-500">
                Riwayat seluruh aktivitas pada sistem.
            </p>

        </div>

        <button
            class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
        >
            Export CSV
        </button>

    </div>

    <div class="grid md:grid-cols-4 gap-4">

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500 text-sm">Total Aktivitas</p>
            <h2 class="text-3xl font-bold mt-2">
                {{ audits.length }}
            </h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500 text-sm">Hari Ini</p>
            <h2 class="text-3xl font-bold mt-2 text-blue-600">
                12
            </h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500 text-sm">Aktivitas Admin</p>
            <h2 class="text-3xl font-bold mt-2 text-indigo-600">
                5
            </h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500 text-sm">Aktivitas User</p>
            <h2 class="text-3xl font-bold mt-2 text-green-600">
                7
            </h2>
        </div>

    </div>

    <div class="bg-white rounded-xl shadow p-5">

        <div class="grid md:grid-cols-2 gap-4">

            <input
                v-model="search"
                placeholder="Cari aktivitas..."
                class="border rounded-lg px-4 py-2"
            />

            <select
                v-model="roleFilter"
                class="border rounded-lg px-4 py-2"
            >
                <option value="">
                    Semua Role
                </option>

                <option>
                    Admin
                </option>

                <option>
                    User
                </option>

            </select>

        </div>

    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-gray-100">

                <tr class="text-left text-sm">

                    <th class="px-5 py-4">
                        Waktu
                    </th>

                    <th class="px-5 py-4">
                        Pengguna
                    </th>

                    <th class="px-5 py-4">
                        Aktivitas
                    </th>

                    <th class="px-5 py-4">
                        IP
                    </th>

                    <th class="px-5 py-4 text-right">
                        Aksi
                    </th>

                </tr>

            </thead>

            <tbody>

                <tr
                    v-for="audit in filtered"
                    :key="audit.id"
                    class="border-t hover:bg-gray-50"
                >

                    <td class="px-5 py-4">
                        {{ audit.created_at }}
                    </td>

                    <td class="px-5 py-4">

                        <div>

                            <div class="font-medium">
                                {{ audit.user }}
                            </div>

                            <span
                                :class="badge(audit.role)"
                                class="text-xs px-2 py-1 rounded-full font-semibold"
                            >
                                {{ audit.role }}
                            </span>

                        </div>

                    </td>

                    <td class="px-5 py-4">

                        <div class="font-medium">
                            {{ audit.action }}
                        </div>

                        <div class="text-sm text-gray-500">
                            {{ audit.description }}
                        </div>

                    </td>

                    <td class="px-5 py-4">
                        {{ audit.ip }}
                    </td>

                    <td class="px-5 py-4">

                        <div class="flex justify-end">

                            <button
                                class="border rounded-lg px-3 py-2 hover:bg-gray-100"
                            >
                                Detail
                            </button>

                        </div>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>
</template>
