<script setup lang="ts">
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppInput from '@/components/atoms/AppInput.vue';
import AppSelect from '@/components/atoms/AppSelect.vue';
import FormField from '@/components/molecules/FormField.vue';
import StatCard from '@/components/molecules/StatCard.vue';
import AppModal from '@/components/organisms/AppModal.vue';
import DetailModal from '@/components/organisms/DetailModal.vue';
import { useModalManager } from '@/composables/useModal';
import type { AdminUser as User } from '@/types/admin';
import type { UserRole as Role } from '@/types/auth';
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
const showDetailModal = computed(() => isModalOpen('detail'));
const showEditModal = computed(() => isModalOpen('edit'));
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
    openModal('detail');
}

function closeDetail() {
    selectedUser.value = null;
    closeModal();
}

function openEdit(user: User) {
    editForm.value = {
        id: user.id,
        name: user.name,
        username: user.username,
        role: user.role,
    };

    openModal('edit');
}

function closeEdit() {
    closeModal();
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

function removeUsernameWhitespace(event: Event, target: 'edit' | 'create') {
    const input = event.target as HTMLInputElement;
    const username = input.value.replace(/\s/g, '');

    if (target === 'edit') {
        editForm.value.username = username;
    } else {
        createForm.value.username = username;
    }
}

const showPasswordModal = computed(() => isModalOpen('password'));
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
                openModal('password');
            },
        },
    );
}

async function copyPassword() {
    await navigator.clipboard.writeText(generatedPassword.value);

    alert('Password berhasil disalin ke clipboard');
}

function closePasswordModal() {
    generatedPassword.value = '';
    closeModal();
}

const { openModal, closeModal, isModalOpen } = useModalManager<
    'detail' | 'edit' | 'password' | 'create'
>();
const showCreateModal = computed(() => isModalOpen('create'));

const createForm = ref({
    name: '',
    username: '',
    role: 'user' as Role,
});

function openCreate() {
    createForm.value = {
        name: '',
        username: '',
        role: 'user',
    };

    openModal('create');
}

function closeCreate() {
    closeModal();

    createForm.value = {
        name: '',
        username: '',
        role: 'user',
    };
}

function submitCreate() {
    router.post('/admin/users', createForm.value, {
        preserveScroll: true,

        onSuccess: () => {
            closeCreate();

            const password = page.props.flash?.generated_password;

            if (!password) {
                return;
            }

            generatedPassword.value = password;
            openModal('password');
        },
    });
}

function deleteUser(user: User) {
    if (!confirm(`Hapus pengguna ${user.name}?`)) {
        return;
    }

    router.delete(`/admin/users/${user.id}`, {
        preserveScroll: true,
    });
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
                @click="openCreate"
                class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
            >
                + Tambah Pengguna
            </button>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <StatCard label="Total" :value="users.length" />
            <StatCard
                label="Admin"
                :value="users.filter((user) => user.role === 'admin').length"
                tone="indigo"
            />
            <StatCard
                label="User"
                :value="users.filter((user) => user.role === 'user').length"
                tone="success"
            />
        </div>
        <div class="rounded-xl bg-white p-5 shadow">
            <div class="grid gap-4 lg:grid-cols-3">
                <AppInput
                    v-model="search"
                    appearance="admin"
                    type="text"
                    placeholder="Cari nama, username, email..."
                    class="rounded-lg border px-4 py-2"
                />

                <AppSelect
                    v-model="roleFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Role</option>

                    <option value="admin">Admin</option>

                    <option value="user">User</option>

                    <option value="display">Display</option>
                </AppSelect>
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
                                        : user.role === 'display'
                                          ? 'bg-amber-100 text-amber-700'
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
                                    @click="deleteUser(user)"
                                >
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <DetailModal
            v-if="showDetailModal && selectedUser"
            title="Detail Pengguna"
            @close="closeDetail"
        >
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
        </DetailModal>

        <AppModal
            v-if="showEditModal"
            title="Edit Pengguna"
            max-width="xl"
            @close="closeEdit"
        >
            <form @submit.prevent="submitEdit" class="space-y-4">
                <FormField label="Nama" appearance="admin" required
                    ><AppInput
                        v-model="editForm.name"
                        appearance="admin"
                        placeholder="Nama pengguna"
                        required
                /></FormField>

                <FormField
                    label="Username"
                    appearance="admin"
                    required
                    :error="page.props.errors.username"
                    ><AppInput
                        v-model="editForm.username"
                        appearance="admin"
                        @keydown.space.prevent
                        @input="removeUsernameWhitespace($event, 'edit')"
                        required
                /></FormField>

                <FormField label="Role" appearance="admin" required
                    ><AppSelect v-model="editForm.role" required
                        ><option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="display">Display</option></AppSelect
                    ></FormField
                >

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
        </AppModal>

        <AppModal
            v-if="showCreateModal"
            title="Tambah Pengguna"
            max-width="xl"
            @close="closeCreate"
        >
            <p class="mb-5 text-sm text-gray-500">
                Password akan dibuat secara otomatis oleh sistem.
            </p>
            <form @submit.prevent="submitCreate" class="space-y-4">
                <FormField label="Nama" appearance="admin" required
                    ><AppInput
                        v-model="createForm.name"
                        appearance="admin"
                        placeholder="Nama pengguna"
                        required
                /></FormField>

                <FormField
                    label="Username"
                    appearance="admin"
                    required
                    :error="page.props.errors.username"
                    ><AppInput
                        v-model="createForm.username"
                        appearance="admin"
                        placeholder="Username"
                        @keydown.space.prevent
                        @input="removeUsernameWhitespace($event, 'create')"
                        required
                /></FormField>

                <FormField label="Role" appearance="admin" required
                    ><AppSelect v-model="createForm.role" required
                        ><option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="display">Display</option></AppSelect
                    ></FormField
                >

                <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                    <p class="font-semibold">Password otomatis</p>

                    <p class="mt-1">
                        Sistem akan membuat password secara otomatis. Pengguna
                        akan diwajibkan mengganti password tersebut saat login
                        pertama kali.
                    </p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="closeCreate"
                        class="rounded-lg border px-4 py-2 hover:bg-gray-100"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                    >
                        Buat Pengguna
                    </button>
                </div>
            </form>
        </AppModal>

        <AppModal
            v-if="showPasswordModal"
            title="Password baru"
            max-width="md"
            @close="closePasswordModal"
        >
            <p class="mt-2 text-gray-500">
                Password ini hanya ditampilkan sekali. Salin dan berikan kepada
                pengguna.
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
                    @click="closePasswordModal"
                >
                    Tutup
                </button>
            </div>
        </AppModal>
    </div>
</template>
