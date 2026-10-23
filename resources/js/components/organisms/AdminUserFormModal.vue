<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { AppButton, AppInput, AppSelect } from '@/components/atoms';
import { FormField } from '@/components/molecules';
import AppModal from '@/components/organisms/AppModal.vue';
import { store, update } from '@/routes/admin/users';
import type { AdminUser } from '@/types/admin';
import type { RoleOption } from '@/types/auth';
import type { FlashPageProps } from '@/types/shared';

const props = defineProps<{ user: AdminUser | null; roles: RoleOption[] }>();
const emit = defineEmits<{ close: []; saved: [password: string] }>();
const page = usePage<FlashPageProps>();
const form = useForm({
    name: props.user?.name ?? '',
    username: props.user?.username ?? '',
    role_id:
        props.user?.role_id ??
        props.roles.find((role) => role.code === 'user')?.id ??
        null,
});

function close() {
    if (!form.processing) {
        emit('close');
    }
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () =>
            emit(
                'saved',
                props.user ? '' : (page.props.flash?.generated_password ?? ''),
            ),
    };

    if (props.user) {
        form.put(update.url(props.user.id), options);
    } else {
        form.post(store.url(), options);
    }
}

function removeUsernameWhitespace(event: Event) {
    form.username = (event.target as HTMLInputElement).value.replace(/\s/g, '');
}
</script>

<template>
    <AppModal
        :title="user ? 'Edit Pengguna' : 'Tambah Pengguna'"
        max-width="xl"
        @close="close"
    >
        <p v-if="!user" class="mb-5 text-sm text-gray-500">
            Password akan dibuat secara otomatis oleh sistem.
        </p>
        <form class="space-y-4" @submit.prevent="submit">
            <FormField
                label="Nama"
                appearance="admin"
                required
                :error="form.errors.name"
            >
                <AppInput
                    v-model="form.name"
                    appearance="admin"
                    placeholder="Nama pengguna"
                    :disabled="form.processing"
                    required
                />
            </FormField>
            <FormField
                label="Username"
                appearance="admin"
                required
                :error="form.errors.username"
            >
                <AppInput
                    v-model="form.username"
                    appearance="admin"
                    placeholder="Username"
                    autocapitalize="none"
                    autocorrect="off"
                    autocomplete="off"
                    :spellcheck="false"
                    :disabled="form.processing"
                    @keydown.space.prevent
                    @input="removeUsernameWhitespace"
                    required
                />
            </FormField>
            <FormField
                label="Role"
                appearance="admin"
                required
                :error="form.errors.role_id"
            >
                <AppSelect
                    v-model="form.role_id"
                    :disabled="form.processing"
                    required
                >
                    <option
                        v-for="role in roles"
                        :key="role.id"
                        :value="role.id"
                    >
                        {{ role.name }}
                    </option>
                </AppSelect>
            </FormField>
            <div
                v-if="!user"
                class="rounded-lg bg-blue-50 p-4 text-sm text-blue-700"
            >
                <p class="font-semibold">Password otomatis</p>
                <p class="mt-1">
                    Sistem akan membuat password secara otomatis. Pengguna akan
                    diwajibkan mengganti password tersebut saat login pertama
                    kali.
                </p>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <AppButton
                    type="button"
                    variant="neutral"
                    size="sm"
                    :disabled="form.processing"
                    @click="close"
                    >Batal</AppButton
                >
                <AppButton
                    type="submit"
                    variant="primary"
                    size="sm"
                    :disabled="form.processing"
                    >{{
                        form.processing
                            ? 'Menyimpan...'
                            : user
                              ? 'Simpan Perubahan'
                              : 'Simpan'
                    }}</AppButton
                >
            </div>
        </form>
    </AppModal>
</template>
