<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { PasswordField } from '@/components/molecules';
import AppModal from '@/components/organisms/AppModal.vue';
import { forcedPassword } from '@/routes/profile';
import type { Auth } from '@/types';

const page = usePage<{ auth: Auth }>();
const isRequired = computed(
    () => page.props.auth.user?.force_change_password === true,
);
const form = useForm({ password: '', password_confirmation: '' });

function submit() {
    form.put(forcedPassword.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AppModal
        v-if="isRequired"
        title="Buat password baru"
        max-width="md"
        :closable="false"
    >
        <form @submit.prevent="submit">
            <p class="mt-2 text-sm text-gray-600">
                Password Anda telah direset oleh admin. Buat password baru untuk
                melanjutkan.
            </p>
            <PasswordField
                id="new-password"
                v-model="form.password"
                class="mt-5"
                label="Password baru"
                :error="form.errors.password"
                autocomplete="new-password"
                required
            />
            <PasswordField
                id="confirm-password"
                v-model="form.password_confirmation"
                label="Konfirmasi password baru"
                :error="form.errors.password_confirmation"
                autocomplete="new-password"
                required
            />
            <button
                class="mt-6 w-full rounded-lg bg-blue-600 px-4 py-2 font-medium text-white disabled:opacity-50"
                type="submit"
                :disabled="form.processing"
            >
                Simpan password
            </button>
        </form>
    </AppModal>
</template>
