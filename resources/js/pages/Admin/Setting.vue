<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
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

const page = usePage<{ name: string }>();

function save() {
    form.put('/admin/settings', {
        onSuccess: () => {
            page.props.name = form.appName;
            document.title = form.appName;
        },
    });
}
</script>

<template>
    <div class="app-page settings-page">
        <div class="page-header page-heading">
            <h1 class="page-title">Pengaturan</h1>

            <p class="text-gray-500">
                Konfigurasi aplikasi peminjaman ruangan.
            </p>
        </div>

        <!-- Informasi -->

        <div class="ui-card">
            <div class="ui-card-header">
                <h2 class="text-lg font-semibold">Informasi Sistem</h2>
            </div>

            <div class="ui-card-body grid gap-4">
                <FormField label="Nama Aplikasi" appearance="admin"
                    ><AppInput v-model="form.appName" appearance="admin"
                /></FormField>

                <FormField label="Logo" appearance="admin"
                    ><input type="file" class="ui-control"
                /></FormField>
            </div>
        </div>

        <!-- Keamanan -->

        <div class="ui-card">
            <div class="ui-card-header">
                <h2 class="text-lg font-semibold">Keamanan</h2>
            </div>

            <div class="ui-card-body">
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

<style scoped>
.settings-page > .ui-card,
.settings-page > .flex {
    width: 100%;
    max-width: var(--ui-form-width);
}
</style>
