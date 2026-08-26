<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppInput from '@/components/atoms/AppInput.vue';
import FormField from '@/components/molecules/FormField.vue';
const props = defineProps<{
    settings: {
        appName: string;
        sessionTimeout: number;
    };
}>();

const form = useForm({
    ...props.settings,
});

function save() {
    form.put('/admin/settings');
}
</script>

<template>
    <div class="w-full max-w-7xl space-y-6">
        <div>
            <h1 class="text-3xl font-bold">Pengaturan</h1>

            <p class="text-gray-500">
                Konfigurasi aplikasi peminjaman ruangan.
            </p>
        </div>

        <!-- Informasi -->

        <div class="rounded-xl bg-white shadow">
            <div class="border-b px-6 py-4">
                <h2 class="text-lg font-semibold">Informasi Sistem</h2>
            </div>

            <div class="grid gap-5 p-6">
                <FormField label="Nama Aplikasi" appearance="admin"
                    ><AppInput v-model="form.appName" appearance="admin"
                /></FormField>

                <FormField label="Logo" appearance="admin"
                    ><input type="file" class="w-full rounded-lg border p-2"
                /></FormField>
            </div>
        </div>

        <!-- Keamanan -->

        <div class="rounded-xl bg-white shadow">
            <div class="border-b px-6 py-4">
                <h2 class="text-lg font-semibold">Keamanan</h2>
            </div>

            <div class="p-6">
                <FormField label="Session Timeout (Menit)" appearance="admin"
                    ><AppInput
                        v-model="form.sessionTimeout"
                        type="number"
                        appearance="admin"
                /></FormField>
            </div>
        </div>

        <div class="flex justify-end">
            <button
                @click="save"
                class="rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700"
            >
                Simpan Perubahan
            </button>
        </div>
    </div>
</template>
