<script setup lang="ts">
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

type Role = 'admin' | 'user';

interface User {
    id: number;
    name: string;
    username: string;
    role: Role;
}

interface pageProps extends InertiaPageProps {
    flash: {
        success?: string;
        error?: string;
        generated_password?: string;
    };
}

const page = usePage<pageProps>();

const props = defineProps<{
    users: User[];
    filters: {
        search: string;
        role: string;
    };
}>();

const users = computed(() => props.users);

const search = ref(props.filters.search ?? '');
const roleFilter = ref(props.filters.role ?? '');
const selectedUser = ref<User | null>(null);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const editForm = ref({
    id: 0,
    name: '',
    username: '',
    role: 'user' as Role,
});

function initials(name: string) {
    return name
        .split(' ')
        .map((x) => x[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
}

function openDetail(user: User) {
    selectedUser.value = user;
    showDetailModal.value = true;
}

function closeDetail() {
    selectedUser.value = null;
    showDetailModal.value = false;
}

function openEdit(user: User) {
    editForm.value = {
        id: user.id,
        name: user.name,
        username: user.username,
        role: user.role,
    };

    showEditModal.value = true;
}

function closeEdit() {
    showEditModal.value = false;
    editForm.value = {
        id: 0,
        name: '',
        username: '',
        role: 'user',
    };
}

function submitEdit() {
    router.put(`/admin/users/${editForm.value.id}`, editForm.value, {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
}

const showPasswordModal = ref(false);
const generatedPassword = ref('');

function resetPassword(user: User) {
    if (!confirm(`Reset password untuk ${user.name}?`)) {
        return;
    }

    router.post(
        `/admin/users/${user.id}/reset-password`,
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                const password = page.props.flash?.generated_password;

                if (!password) {
                    return;
                }

                generatedPassword.value = password;
                showPasswordModal.value = true;
            },
        },
    );
}

async function copyPassword() {
    await navigator.clipboard.writeText(generatedPassword.value);

    alert('Password berhasil disalin ke clipboard');
}

function closeModal() {
    generatedPassword.value = '';
    showPasswordModal.value = false;
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Pengguna</h1>

                <p class="text-gray-500">Kelola akun pengguna sistem.</p>
            </div>

            <button
                class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
            >
                + Tambah Pengguna
            </button>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Total</p>

                <h2 class="mt-2 text-3xl font-bold">
                    {{ users.length }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">Admin</p>

                <h2 class="mt-2 text-3xl font-bold text-indigo-600">
                    {{ users.filter((u) => u.role === 'admin').length }}
                </h2>
            </div>

            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">User</p>

                <h2 class="mt-2 text-3xl font-bold text-green-600">
                    {{ users.filter((u) => u.role === 'user').length }}
                </h2>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow">
            <div class="grid gap-4 lg:grid-cols-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari nama, username, email..."
                    class="rounded-lg border px-4 py-2"
                />

                <select
                    v-model="roleFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Role</option>

                    <option value="admin">Admin</option>

                    <option value="user">User</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr class="text-left text-sm">
                        <th class="px-5 py-4">Pengguna</th>

                        <th class="px-5 py-4">Username</th>

                        <th class="px-5 py-4">Role</th>

                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="user in users"
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
                                </div>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            {{ user.username }}
                        </td>

                        <td class="px-5 py-4">
                            <span
                                :class="
                                    user.role === 'admin'
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'bg-green-100 text-green-700'
                                "
                                class="rounded-full px-3 py-1 text-xs font-semibold uppercase"
                            >
                                {{ user.role }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <button
                                    @click="openDetail(user)"
                                    class="rounded-lg border px-3 py-2 hover:bg-gray-100"
                                >
                                    Detail
                                </button>

                                <button
                                    @click="openEdit(user)"
                                    class="rounded-lg bg-yellow-500 px-3 py-2 text-white hover:bg-yellow-600"
                                >
                                    Edit
                                </button>

                                <button
                                    class="rounded-lg bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700"
                                    @click="resetPassword(user)"
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

        <div
            v-if="showDetailModal && selectedUser"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        >
            <div class="w-full max-w-lg rounded-xl bg-white p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-bold">Detail Pengguna</h2>

                    <button
                        @click="closeDetail"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-4 text-sm text-gray-700">
                    <div>
                        <p class="font-semibold text-gray-500">Nama</p>
                        <p>{{ selectedUser.name }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-500">Username</p>
                        <p>{{ selectedUser.username }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-500">Role</p>
                        <span
                            :class="
                                selectedUser.role === 'admin'
                                    ? 'bg-indigo-100 text-indigo-700'
                                    : 'bg-green-100 text-green-700'
                            "
                            class="mt-1 inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase"
                        >
                            {{ selectedUser.role }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        >
            <div class="w-full max-w-xl rounded-xl bg-white p-6">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-xl font-bold">Edit Pengguna</h2>

                    <button
                        @click="closeEdit"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium"
                            >Nama</label
                        >
                        <input
                            v-model="editForm.name"
                            type="text"
                            class="w-full rounded-lg border px-4 py-2"
                            required
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium"
                            >Username</label
                        >
                        <input
                            v-model="editForm.username"
                            type="text"
                            class="w-full rounded-lg border px-4 py-2"
                            required
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium"
                            >Role</label
                        >
                        <select
                            v-model="editForm.role"
                            class="w-full rounded-lg border px-4 py-2"
                        >
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeEdit"
                            class="rounded-lg border px-4 py-2 hover:bg-gray-100"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="showPasswordModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6">
                <h2 class="text-xl font-bold">Password baru</h2>

                <p class="mt-2 text-gray-500">
                    Password ini hanya ditampilkan sekali. Salin dan berikan
                    kepada pengguna.
                </p>

                <div
                    class="mt-4 flex items-center justify-between rounded-lg bg-gray-100 p-3"
                >
                    <code class="font-mono text-lg">
                        {{ generatedPassword }}
                    </code>

                    <button
                        class="rounded bg-blue-600 px-3 py-2 text-white"
                        @click="copyPassword"
                    >
                        Copy
                    </button>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        class="rounded-lg bg-gray-800 px-5 py-2 text-white"
                        @click="closeModal"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
