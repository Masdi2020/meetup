<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, Copy } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import {
    ActionIconButton,
    AppButton,
    AppInput,
    AppSelect,
} from '@/components/atoms';
import { FormField, PageHeader, StatCard } from '@/components/molecules';
import {
    AdminSearchPanel,
    AppModal,
    ConfirmModal,
    DetailModal,
} from '@/components/organisms';
import { useAdminFilters } from '@/composables/admin/useAdminFilters';
import { useModalManager } from '@/composables/useModal';
import {
    destroy,
    index,
    resetPassword as resetPasswordRoute,
    store,
    update,
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
const assignableRoles = computed(() =>
    props.roles.filter(
        (role) => props.canManageUsers || role.code !== 'super_admin',
    ),
);
const defaultRoleId = computed(
    () => props.roles.find((role) => role.code === 'user')?.id ?? null,
);

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
const editForm = ref({
    id: 0,
    name: '',
    username: '',
    role_id: defaultRoleId.value,
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
        role_id: user.role_id,
    };

    openModal('edit');
}

function closeEdit() {
    closeModal();
    editForm.value = {
        id: 0,
        name: '',
        username: '',
        role_id: defaultRoleId.value,
    };
}

function submitEdit() {
    router.put(update.url(editForm.value.id), editForm.value, {
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

const generatedPassword = ref('');
const copyStatus = ref<'idle' | 'copied' | 'error'>('idle');
const copyLockDuration = 3000;
let copyUnlockTimer: ReturnType<typeof setTimeout> | undefined;

function openPasswordModal(password: string) {
    if (copyUnlockTimer) {
        window.clearTimeout(copyUnlockTimer);
    }

    generatedPassword.value = password;
    copyStatus.value = 'idle';
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

async function copyPassword() {
    if (copyStatus.value === 'copied' || !generatedPassword.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(generatedPassword.value);
        copyStatus.value = 'copied';

        copyUnlockTimer = window.setTimeout(() => {
            copyStatus.value = 'idle';
            copyUnlockTimer = undefined;
        }, copyLockDuration);
    } catch {
        copyStatus.value = 'error';
    }
}

function closePasswordModal() {
    if (copyUnlockTimer) {
        window.clearTimeout(copyUnlockTimer);
        copyUnlockTimer = undefined;
    }

    copyStatus.value = 'idle';
    generatedPassword.value = '';
    closeModal();
}

onBeforeUnmount(() => {
    if (copyUnlockTimer) {
        window.clearTimeout(copyUnlockTimer);
    }
});

const { openModal, closeModal, isModalOpen } = useModalManager<
    'detail' | 'edit' | 'password' | 'create'
>();
const showPasswordModal = computed(() => isModalOpen('password'));
const showCreateModal = computed(() => isModalOpen('create'));

const createForm = ref({
    name: '',
    username: '',
    role_id: defaultRoleId.value,
});

function openCreate() {
    createForm.value = {
        name: '',
        username: '',
        role_id: defaultRoleId.value,
    };

    openModal('create');
}

function closeCreate() {
    closeModal();

    createForm.value = {
        name: '',
        username: '',
        role_id: defaultRoleId.value,
    };
}

function submitCreate() {
    router.post(store.url(), createForm.value, {
        preserveScroll: true,

        onSuccess: () => {
            closeCreate();

            const password = page.props.flash?.generated_password;

            if (!password) {
                return;
            }

            openPasswordModal(password);
        },
    });
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
                        autocapitalize="none"
                        autocorrect="off"
                        autocomplete="off"
                        :spellcheck="false"
                        @keydown.space.prevent
                        @input="removeUsernameWhitespace($event, 'edit')"
                        required
                /></FormField>

                <FormField
                    label="Role"
                    appearance="admin"
                    required
                    :error="page.props.errors.role_id"
                >
                    <AppSelect v-model="editForm.role_id" required>
                        <option
                            v-for="role in assignableRoles"
                            :key="role.id"
                            :value="role.id"
                        >
                            {{ role.name }}
                        </option>
                    </AppSelect>
                </FormField>

                <div class="flex justify-end gap-3 pt-2">
                    <AppButton
                        type="button"
                        @click="closeEdit"
                        variant="neutral"
                        size="sm"
                    >
                        Batal
                    </AppButton>

                    <AppButton type="submit" variant="primary" size="sm">
                        Simpan Perubahan
                    </AppButton>
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
                        autocapitalize="none"
                        autocorrect="off"
                        autocomplete="off"
                        :spellcheck="false"
                        @keydown.space.prevent
                        @input="removeUsernameWhitespace($event, 'create')"
                        required
                /></FormField>

                <FormField
                    label="Role"
                    appearance="admin"
                    required
                    :error="page.props.errors.role_id"
                >
                    <AppSelect v-model="createForm.role_id" required>
                        <option
                            v-for="role in assignableRoles"
                            :key="role.id"
                            :value="role.id"
                        >
                            {{ role.name }}
                        </option>
                    </AppSelect>
                </FormField>

                <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                    <p class="font-semibold">Password otomatis</p>

                    <p class="mt-1">
                        Sistem akan membuat password secara otomatis. Pengguna
                        akan diwajibkan mengganti password tersebut saat login
                        pertama kali.
                    </p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <AppButton
                        type="button"
                        @click="closeCreate"
                        variant="neutral"
                        size="sm"
                    >
                        Batal
                    </AppButton>

                    <AppButton type="submit" variant="primary" size="sm">
                        Buat Pengguna
                    </AppButton>
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

            <div class="mt-4">
                <div class="flex items-center gap-3 rounded-lg bg-gray-100 p-3">
                    <code
                        class="min-w-0 flex-1 overflow-x-auto font-mono text-lg font-semibold text-gray-800"
                    >
                        {{ generatedPassword }}
                    </code>

                    <button
                        type="button"
                        :disabled="copyStatus === 'copied'"
                        :aria-label="
                            copyStatus === 'copied'
                                ? 'Password sudah disalin'
                                : 'Salin password'
                        "
                        :title="
                            copyStatus === 'copied'
                                ? 'Password sudah disalin'
                                : 'Salin password'
                        "
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-emerald-600"
                        @click="copyPassword"
                    >
                        <Check
                            v-if="copyStatus === 'copied'"
                            class="h-5 w-5"
                            aria-hidden="true"
                        />
                        <Copy v-else class="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>

                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="-translate-y-1 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="-translate-y-1 opacity-0"
                >
                    <div
                        v-if="copyStatus !== 'idle'"
                        class="mt-2 flex justify-end"
                    >
                        <p
                            :class="
                                copyStatus === 'copied'
                                    ? 'bg-emerald-600'
                                    : 'bg-red-600'
                            "
                            class="rounded-lg px-3 py-2 text-sm font-medium text-white shadow-lg"
                            :role="copyStatus === 'copied' ? 'status' : 'alert'"
                        >
                            {{
                                copyStatus === 'copied'
                                    ? 'Password berhasil disalin.'
                                    : 'Password gagal disalin. Silakan coba kembali.'
                            }}
                        </p>
                    </div>
                </Transition>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    class="rounded-lg bg-gray-800 px-5 py-2 text-white transition hover:bg-gray-900"
                    @click="closePasswordModal"
                >
                    Tutup
                </button>
            </div>
        </AppModal>

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
