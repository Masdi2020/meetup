<script setup lang="ts">
import { computed, ref } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";

defineOptions({
    layout: AdminLayout,
});

type Role = "admin" | "user";

interface User {
    id: number;
    name: string;
    username: string;
    email: string;
    role: Role;
    active: boolean;
    lastLogin: string;
}

const search = ref("");
const roleFilter = ref("");
const statusFilter = ref("");

const users = ref<User[]>([
    {
        id: 1,
        name: "Administrator",
        username: "admin",
        email: "admin@example.com",
        role: "admin",
        active: true,
        lastLogin: "Hari ini 08:30",
    },
    {
        id: 2,
        name: "Dimas Ramadhani",
        username: "dimas",
        email: "dimas@example.com",
        role: "user",
        active: true,
        lastLogin: "Kemarin 16:40",
    },
    {
        id: 3,
        name: "Andi Saputra",
        username: "andi",
        email: "andi@example.com",
        role: "user",
        active: false,
        lastLogin: "10 Jul 2026",
    },
]);

const filteredUsers = computed(() =>
    users.value.filter((user) => {
        const keyword =
            user.name.toLowerCase().includes(search.value.toLowerCase()) ||
            user.username.toLowerCase().includes(search.value.toLowerCase()) ||
            user.email.toLowerCase().includes(search.value.toLowerCase());

        const role =
            !roleFilter.value || user.role === roleFilter.value;

        const status =
            !statusFilter.value ||
            (statusFilter.value === "active" && user.active) ||
            (statusFilter.value === "inactive" && !user.active);

        return keyword && role && status;
    }),
);

function initials(name: string) {
    return name
        .split(" ")
        .map((x) => x[0])
        .join("")
        .substring(0, 2)
        .toUpperCase();
}
</script>

<template>
    <div class="space-y-6">

        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-3xl font-bold">
                    Pengguna
                </h1>

                <p class="text-gray-500">
                    Kelola akun pengguna sistem.
                </p>
            </div>

            <button
                class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
            >
                + Tambah Pengguna
            </button>

        </div>

        <!-- Statistik -->

        <div class="grid gap-4 md:grid-cols-4">

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    Total
                </p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ users.length }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    Admin
                </p>

                <h2 class="mt-2 text-3xl font-bold text-indigo-600">
                    {{ users.filter(u => u.role === "admin").length }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    User
                </p>

                <h2 class="mt-2 text-3xl font-bold text-green-600">
                    {{ users.filter(u => u.role === "user").length }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    Aktif
                </p>

                <h2 class="mt-2 text-3xl font-bold text-blue-600">
                    {{ users.filter(u => u.active).length }}
                </h2>
            </div>

        </div>

        <!-- Filter -->

        <div class="rounded-xl bg-white p-5 shadow">

            <div class="grid gap-4 lg:grid-cols-3">

                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari nama, username, email..."
                    class="rounded-lg border px-4 py-2"
                >

                <select
                    v-model="roleFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">
                        Semua Role
                    </option>

                    <option value="admin">
                        Admin
                    </option>

                    <option value="user">
                        User
                    </option>

                </select>

                <select
                    v-model="statusFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">
                        Semua Status
                    </option>

                    <option value="active">
                        Aktif
                    </option>

                    <option value="inactive">
                        Nonaktif
                    </option>

                </select>

            </div>

        </div>

        <!-- Table -->

        <div class="overflow-hidden rounded-xl bg-white shadow">

            <table class="min-w-full">

                <thead class="bg-gray-100">

                    <tr class="text-left text-sm">

                        <th class="px-5 py-4">
                            Pengguna
                        </th>

                        <th class="px-5 py-4">
                            Username
                        </th>

                        <th class="px-5 py-4">
                            Role
                        </th>

                        <th class="px-5 py-4">
                            Status
                        </th>

                        <th class="px-5 py-4">
                            Login Terakhir
                        </th>

                        <th class="px-5 py-4 text-right">
                            Aksi
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="user in filteredUsers"
                        :key="user.id"
                        class="border-t hover:bg-gray-50"
                    >

                        <td class="px-5 py-4">

                            <div class="flex items-center gap-3">

                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 font-semibold text-white"
                                >
                                    {{ initials(user.name) }}
                                </div>

                                <div>

                                    <div class="font-medium">
                                        {{ user.name }}
                                    </div>

                                    <div class="text-sm text-gray-500">
                                        {{ user.email }}
                                    </div>

                                </div>

                            </div>

                        </td>

                        <td class="px-5 py-4">
                            {{ user.username }}
                        </td>

                        <td class="px-5 py-4">

                            <span
                                :class="user.role === 'admin'
                                    ? 'bg-indigo-100 text-indigo-700'
                                    : 'bg-green-100 text-green-700'"
                                class="rounded-full px-3 py-1 text-xs font-semibold uppercase"
                            >
                                {{ user.role }}
                            </span>

                        </td>

                        <td class="px-5 py-4">

                            <span
                                :class="user.active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'"
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                            >
                                {{ user.active ? "Aktif" : "Nonaktif" }}
                            </span>

                        </td>

                        <td class="px-5 py-4">
                            {{ user.lastLogin }}
                        </td>

                        <td class="px-5 py-4">

                            <div class="flex justify-end gap-2">

                                <button
                                    class="rounded-lg border px-3 py-2 hover:bg-gray-100"
                                >
                                    Detail
                                </button>

                                <button
                                    class="rounded-lg bg-yellow-500 px-3 py-2 text-white hover:bg-yellow-600"
                                >
                                    Edit
                                </button>

                                <button
                                    class="rounded-lg bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700"
                                >
                                    Reset Password
                                </button>

                                <button
                                    class="rounded-lg bg-red-600 px-3 py-2 text-white hover:bg-red-700"
                                >
                                    Hapus
                                </button>

                            </div>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>
</template>
