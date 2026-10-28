<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ActionIconButton, AppButton, AppSelect } from '@/components/atoms';
import { PageHeader, StatCard } from '@/components/molecules';
import {
    AdminSearchPanel,
    AdminUserFormModal,
    GeneratedPasswordModal,
    ConfirmModal,
    DetailModal,
} from '@/components/organisms';
import { useAdminFilters } from '@/composables/admin/useAdminFilters';
import { useModalManager } from '@/composables/useModal';
import {
    destroy,
    index,
    resetPassword as resetPasswordRoute,
} from '@/routes/admin/users';
import type { AdminUser as User } from '@/types/admin';
import type { RoleOption } from '@/types/auth';
import type { FlashPageProps } from '@/types/shared';

const page = usePage<FlashPageProps>();

const props = defineProps<{
    users: User[];
    roles: RoleOption[];
    canManageUsers: boolean;
    filters: {
        search: string;
        role: string;
    };
}>();

const users = computed(() => props.users);
function roleName(code: string) {
    return props.roles.find((role) => role.code === code)?.name ?? code;
}

const search = ref(props.filters.search ?? '');
const roleFilter = ref(props.filters.role ?? '');
const { applyFilters, resetFilters } = useAdminFilters(index.url(), {
    debouncedSources: [search],
    instantSources: [roleFilter],
    query: () => ({ search: search.value, role: roleFilter.value }),
    reset: () => {
        search.value = '';
        roleFilter.value = '';
    },
});
const selectedUser = ref<User | null>(null);
const userToConfirm = ref<User | null>(null);
const confirmAction = ref<'reset' | 'delete' | null>(null);
const showDetailModal = computed(() => isModalOpen('detail'));
const showEditModal = computed(() => isModalOpen('edit'));
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
    selectedUser.value = user;
    openModal('edit');
}

const generatedPassword = ref('');

function openPasswordModal(password: string) {
    generatedPassword.value = password;
    openModal('password');
}

function openResetModal(user: User) {
    userToConfirm.value = user;
    confirmAction.value = 'reset';
}

function resetPassword() {
    if (!userToConfirm.value) {
        return;
    }

    router.post(
        resetPasswordRoute.url(userToConfirm.value.id),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                const password = page.props.flash?.generated_password;

                if (!password) {
                    return;
                }

                userToConfirm.value = null;
                confirmAction.value = null;
                openPasswordModal(password);
            },
        },
    );
}

function closePasswordModal() {
    generatedPassword.value = '';
    closeModal();
}

const { openModal, closeModal, isModalOpen } = useModalManager<
    'detail' | 'edit' | 'password' | 'create'
>();
const showPasswordModal = computed(() => isModalOpen('password'));
const showCreateModal = computed(() => isModalOpen('create'));

function openCreate() {
    selectedUser.value = null;
    openModal('create');
}

function onUserSaved(password: string) {
    closeModal();

    if (password) {
        openPasswordModal(password);
    }
}

function openDeleteModal(user: User) {
    userToConfirm.value = user;
    confirmAction.value = 'delete';
}

function closeConfirmModal() {
    userToConfirm.value = null;
    confirmAction.value = null;
}

function deleteUser() {
    if (!userToConfirm.value) {
        return;
    }

    router.delete(destroy.url(userToConfirm.value.id), {
        preserveScroll: true,
        onSuccess: () => closeConfirmModal(),
    });
}
</script>

<template>
    <div class="app-page">
        <PageHeader
            title="Pengguna"
            :description="
                canManageUsers
                    ? 'Kelola akun pengguna sistem.'
                    : 'Lihat akun dan reset password pengguna.'
            "
        >
            <AppButton
                v-if="canManageUsers"
                @click="openCreate"
                variant="primary"
                size="lg"
            >
                + Tambah Pengguna
            </AppButton>
        </PageHeader>

        <div class="stats-grid">
            <StatCard label="Total" :value="users.length" />
            <StatCard
                label="Super Admin"
                :value="
                    users.filter((user) => user.role === 'super_admin').length
                "
                tone="indigo"
            />
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
        <AdminSearchPanel
            v-model="search"
            placeholder="Cari nama atau username..."
            :filter-columns="1"
            @search="applyFilters"
            @reset="resetFilters"
        >
            <template #filters>
                <AppSelect
                    v-model="roleFilter"
                    class="rounded-lg border px-4 py-2"
                >
                    <option value="">Semua Role</option>

                    <option
                        v-for="role in roles"
                        :key="role.id"
                        :value="role.code"
                    >
                        {{ role.name }}
                    </option>
                </AppSelect>
            </template>
        </AdminSearchPanel>

        <div class="ui-card overflow-x-auto">
            <table class="w-full min-w-[720px]">
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
                                    user.role === 'admin' ||
                                    user.role === 'super_admin'
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : user.role === 'display'
                                          ? 'bg-amber-100 text-amber-700'
                                          : 'bg-green-100 text-green-700'
                                "
                                class="rounded-full px-3 py-1 text-xs font-semibold uppercase"
                            >
                                {{ roleName(user.role) }}
                            </span>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <ActionIconButton
                                    action="detail"
                                    @click="openDetail(user)"
                                />

                                <ActionIconButton
                                    action="edit"
                                    v-if="canManageUsers"
                                    @click="openEdit(user)"
                                />

                                <ActionIconButton
                                    action="reset"
                                    v-if="
                                        canManageUsers ||
                                        user.role !== 'super_admin'
                                    "
                                    @click="openResetModal(user)"
                                />

                                <ActionIconButton
                                    action="delete"
                                    v-if="canManageUsers"
                                    @click="openDeleteModal(user)"
                                />
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
                            selectedUser.role === 'admin' ||
                            selectedUser.role === 'super_admin'
                                ? 'bg-indigo-100 text-indigo-700'
                                : 'bg-green-100 text-green-700'
                        "
                        class="mt-1 inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase"
                    >
                        {{ roleName(selectedUser.role) }}
                    </span>
                </div>
            </div>
        </DetailModal>

        <AdminUserFormModal
            v-if="canManageUsers && (showCreateModal || showEditModal)"
            :key="showEditModal ? selectedUser?.id : 'create'"
            :user="showEditModal ? selectedUser : null"
            :roles="roles"
            @close="closeModal"
            @saved="onUserSaved"
        />
        <GeneratedPasswordModal
            v-if="showPasswordModal"
            :password="generatedPassword"
            @close="closePasswordModal"
        />

        <ConfirmModal
            v-if="userToConfirm && confirmAction"
            :title="
                confirmAction === 'reset' ? 'Reset Password' : 'Hapus Pengguna'
            "
            :message="
                confirmAction === 'reset'
                    ? `Reset password untuk ${userToConfirm.name}?`
                    : `Pengguna ${userToConfirm.name} akan dihapus. Tindakan ini tidak dapat dibatalkan.`
            "
            :confirm-label="
                confirmAction === 'reset' ? 'Reset Password' : 'Hapus'
            "
            @close="closeConfirmModal"
            @confirm="
                confirmAction === 'reset' ? resetPassword() : deleteUser()
            "
        />
    </div>
</template>
