<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Auth } from '@/types';

const page = usePage<{ auth: Auth }>();
const isRequired = computed(
    () => page.props.auth.user?.force_change_password === true,
);
const form = useForm({ password: '', password_confirmation: '' });

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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
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
            <label class="mt-5 block text-sm font-medium" for="new-password"
                >Password baru</label
            >
            <input
                id="new-password"
                v-model="form.password"
                type="password"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"
                autocomplete="new-password"
                required
            />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                {{ form.errors.password }}
            </p>
            <label class="mt-4 block text-sm font-medium" for="confirm-password"
                >Konfirmasi password baru</label
            >
            <input
                id="confirm-password"
                v-model="form.password_confirmation"
                type="password"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"
                autocomplete="new-password"
                required
            />
            <p
                v-if="form.errors.password_confirmation"
                class="mt-1 text-sm text-red-600"
            >
                {{ form.errors.password_confirmation }}
            </p>
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
