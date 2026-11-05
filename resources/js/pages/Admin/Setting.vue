<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { AppButton, AppInput } from '@/components/atoms';
import { FormField, PageHeader } from '@/components/molecules';
import { update } from '@/routes/admin/settings';
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
    form.put(update.url(), {
        onSuccess: () => {
            page.props.name = form.appName;
            document.title = form.appName;
        },
    });
}
</script>

<template>
    <div class="app-page">
        <PageHeader
            title="Pengaturan"
            description="Konfigurasi aplikasi peminjaman ruangan."
        />

        <!-- Informasi -->

        <div class="ui-card">
            <div class="ui-card-header">
                <h2 class="text-lg font-semibold">Informasi Sistem</h2>
            </div>

            <div class="ui-card-body grid gap-field">
                <FormField
                    label="Nama Aplikasi"
                    for-id="app-name"
                    appearance="admin"
                    ><AppInput
                        id="app-name"
                        v-model="form.appName"
                        appearance="admin"
                /></FormField>

                <FormField label="Logo" for-id="app-logo" appearance="admin"
                    ><input id="app-logo" type="file" class="ui-control"
                /></FormField>
            </div>
        </div>

        <!-- Keamanan -->

        <div class="ui-card">
            <div class="ui-card-header">
                <h2 class="text-lg font-semibold">Keamanan</h2>
            </div>

            <div class="ui-card-body">
                <FormField
                    label="Batas Waktu Sesi (Menit)"
                    for-id="session-timeout"
                    appearance="admin"
                    ><AppInput
                        id="session-timeout"
                        v-model="form.sessionTimeout"
                        type="number"
                        appearance="admin"
                /></FormField>
            </div>
        </div>

        <div class="flex justify-end">
            <AppButton @click="save" variant="primary" size="lg">
                Simpan Perubahan
            </AppButton>
        </div>
    </div>
</template>
