<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';
import type { Ref } from 'vue';
import PasswordField from '@/components/molecules/PasswordField.vue';
import type { Auth } from '@/types';

const page = usePage<{ auth: Auth }>();
const isRequired = computed(
    () => page.props.auth.user?.force_change_password === true,
);
const form = useForm({ password: '', password_confirmation: '' });
const sidebarOffset = inject<Ref<string>>('sidebarOffset', ref('0px'));

function submit() {
    form.put('/profile/forced-password', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <div
        v-if="isRequired"
        class="fixed inset-y-0 right-0 z-50 flex items-center justify-center bg-black/50 p-4 transition-[left] duration-200"
        :style="{ left: sidebarOffset }"
        role="dialog"
        aria-modal="true"
    >
        <form
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
            @submit.prevent="submit"
        >
            <h2 class="text-xl font-bold">Buat password baru</h2>
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
    </div>
</template>
