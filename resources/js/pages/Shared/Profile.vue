<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PasswordField from '@/components/molecules/PasswordField.vue';
import type { ProfilePageProps } from '@/types/auth';

const page = usePage();
const props = page.props as unknown as ProfilePageProps;

const form = useForm({
    name: props.user?.name ?? '',
    username: props.user?.username ?? '',
    current_password: '',
    password: '',
    password_confirmation: '',
});

const originalProfile = ref({
    name: props.user?.name ?? '',
    username: props.user?.username ?? '',
});

const activeTab = ref<'profile' | 'password'>('profile');

const profileChanged = computed(() => {
    return (
        form.name !== originalProfile.value.name ||
        form.username !== originalProfile.value.username
    );
});

function saveProfile() {
    form.post('/profile', {
        preserveState: true,
        preserveScroll: true,
        only: ['errors', 'flash'],
        onSuccess: () => {
            originalProfile.value = {
                name: form.name,
                username: form.username,
            };
        },
    });
}

function removeUsernameWhitespace(event: Event) {
    const input = event.target as HTMLInputElement;
    form.username = input.value.replace(/\s/g, '');
}

function changePassword() {
    form.put('/profile/password', {
        preserveState: true,
        preserveScroll: true,
        only: ['errors', 'flash'],
        onSuccess: () => {
            form.current_password = '';
            form.password = '';
            form.password_confirmation = '';
        },
    });
}
</script>

<template>
    <div class="app-page profile-page">
        <div class="page-header page-heading">
            <h1 class="page-title">Pengaturan Profil</h1>
            <p>Kelola nama, username, dan password Anda.</p>
        </div>

        <div class="profile-card ui-card ui-card-body">
            <div class="tabs">
                <button
                    :class="{ active: activeTab === 'profile' }"
                    @click="activeTab = 'profile'"
                >
                    Profil
                </button>
                <button
                    :class="{ active: activeTab === 'password' }"
                    @click="activeTab = 'password'"
                >
                    Ganti Password
                </button>
            </div>

            <div class="content">
                <form
                    v-if="activeTab === 'profile'"
                    @submit.prevent="saveProfile"
                >
                    <div class="field">
                        <label>Nama</label>
                        <input v-model="form.name" type="text" required />
                        <p v-if="form.errors.name" class="error">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="field">
                        <label>Username</label>
                        <input
                            v-model="form.username"
                            type="text"
                            autocapitalize="none"
                            autocorrect="off"
                            autocomplete="username"
                            :spellcheck="false"
                            @keydown.space.prevent
                            @input="removeUsernameWhitespace"
                            required
                        />
                        <p v-if="form.errors.username" class="error">
                            {{ form.errors.username }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="!profileChanged || form.processing"
                    >
                        Simpan Profil
                    </button>
                </form>

                <form
                    v-if="activeTab === 'password'"
                    @submit.prevent="changePassword"
                >
                    <PasswordField
                        id="current-password"
                        v-model="form.current_password"
                        label="Password Lama"
                        :error="form.errors.current_password"
                        autocomplete="current-password"
                        required
                    />

                    <PasswordField
                        id="new-password"
                        v-model="form.password"
                        label="Password Baru"
                        :error="form.errors.password"
                        autocomplete="new-password"
                        required
                    />

                    <PasswordField
                        id="confirm-password"
                        v-model="form.password_confirmation"
                        label="Ulangi Password Baru"
                        :error="form.errors.password_confirmation"
                        autocomplete="new-password"
                        required
                    />

                    <button type="submit">Ubah Password</button>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-header p {
    color: #64748b;
}

.profile-card {
    width: 100%;
    max-width: var(--ui-form-width);
}
.content form {
    display: grid;
    gap: var(--ui-gap);
}
.content :deep(.form-field) {
    margin-bottom: 0;
}

.tabs {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin: 0 0 var(--ui-gap);
}

.tabs button {
    min-height: var(--ui-control-height);
    padding: 10px 16px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: var(--ui-radius);
    cursor: pointer;
    transition: all 0.2s ease;
}

.tabs button.active {
    background: #2563eb;
    color: white;
    border-color: transparent;
}

.content {
    display: grid;
    gap: 16px;
}

.field {
    display: grid;
    gap: 8px;
}

.field label {
    font-weight: 600;
}

.field input {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: var(--ui-radius);
    min-height: var(--ui-control-height);
    padding: 10px 12px;
}

.error {
    color: #dc2626;
    font-size: 13px;
}

button[type='submit'] {
    width: fit-content;
    min-height: var(--ui-control-height);
    padding: 10px 16px;
    border: none;
    border-radius: var(--ui-radius);
    background: #2563eb;
    color: white;
    cursor: pointer;
}

button[type='submit']:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
</style>
