<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';

interface PageProps {
    user: {
        name: string;
        username: string;
    };
    flash: {
        success?: string;
        error?: string;
    };
}

const page = usePage();
const props = page.props as unknown as PageProps;

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
const toast = ref<{ message: string; type: 'success' | 'error' } | null>(null);
let toastTimer = 0;
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const profileChanged = computed(() => {
    return (
        form.name !== originalProfile.value.name ||
        form.username !== originalProfile.value.username
    );
});

function showToast(message: string, type: 'success' | 'error') {
    toast.value = { message, type };
    window.clearTimeout(toastTimer);

    toastTimer = window.setTimeout(() => {
        toast.value = null;
    }, 3500);
}

watchEffect(() => {
    if (props.flash?.success) {
        showToast(props.flash.success, 'success');
    }

    if (props.flash?.error) {
        showToast(props.flash.error, 'error');
    }
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

function changePassword() {
    form.put('/profile/password', {
        preserveState: true,
        preserveScroll: true,
        only: ['errors', 'flash'],
    });
}
</script>

<template>
    <div class="profile-page">
        <div class="header">
            <h1>Pengaturan Profil</h1>
            <p>Kelola nama, username, dan password Anda.</p>
        </div>

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
            <div v-if="toast" :class="['toast', toast.type]">
                {{ toast.message }}
            </div>

            <form v-if="activeTab === 'profile'" @submit.prevent="saveProfile">
                <div class="field">
                    <label>Nama</label>
                    <input v-model="form.name" type="text" required />
                    <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
                </div>

                <div class="field">
                    <label>Username</label>
                    <input v-model="form.username" type="text" required />
                    <p v-if="form.errors.username" class="error">{{ form.errors.username }}</p>
                </div>

                <button type="submit" :disabled="!profileChanged || form.processing">
                    Simpan Profil
                </button>
            </form>

            <form v-if="activeTab === 'password'" @submit.prevent="changePassword">
                <div class="field password-field">
                    <label>Password Lama</label>
                    <div class="password-input-wrapper">
                        <input
                            v-model="form.current_password"
                            :type="showCurrentPassword ? 'text' : 'password'"
                            required
                        />
                        <button type="button" class="password-toggle" @click="showCurrentPassword = !showCurrentPassword" aria-label="Toggle current password visibility">
                            <svg v-if="showCurrentPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 1l22 22" />
                                <path d="M17.94 17.94A10.12 10.12 0 0 1 12 19c-5 0-9.27-3.11-11-7.5a19.79 19.79 0 0 1 4.55-6.38" />
                                <path d="M9.53 9.53a3.5 3.5 0 0 0 4.94 4.94" />
                                <path d="M14.12 14.12A3.5 3.5 0 0 1 9.88 9.88" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="form.errors.current_password" class="error">{{ form.errors.current_password }}</p>
                </div>

                <div class="field password-field">
                    <label>Password Baru</label>
                    <div class="password-input-wrapper">
                        <input
                            v-model="form.password"
                            :type="showNewPassword ? 'text' : 'password'"
                            required
                        />
                        <button type="button" class="password-toggle" @click="showNewPassword = !showNewPassword" aria-label="Toggle new password visibility">
                            <svg v-if="showNewPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 1l22 22" />
                                <path d="M17.94 17.94A10.12 10.12 0 0 1 12 19c-5 0-9.27-3.11-11-7.5a19.79 19.79 0 0 1 4.55-6.38" />
                                <path d="M9.53 9.53a3.5 3.5 0 0 0 4.94 4.94" />
                                <path d="M14.12 14.12A3.5 3.5 0 0 1 9.88 9.88" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="error">{{ form.errors.password }}</p>
                </div>

                <div class="field password-field">
                    <label>Ulangi Password Baru</label>
                    <div class="password-input-wrapper">
                        <input
                            v-model="form.password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            required
                        />
                        <button type="button" class="password-toggle" @click="showConfirmPassword = !showConfirmPassword" aria-label="Toggle confirm password visibility">
                            <svg v-if="showConfirmPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 1l22 22" />
                                <path d="M17.94 17.94A10.12 10.12 0 0 1 12 19c-5 0-9.27-3.11-11-7.5a19.79 19.79 0 0 1 4.55-6.38" />
                                <path d="M9.53 9.53a3.5 3.5 0 0 0 4.94 4.94" />
                                <path d="M14.12 14.12A3.5 3.5 0 0 1 9.88 9.88" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit">Ubah Password</button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.profile-page {
    max-width: 760px;
    margin: 0 auto;
    background: white;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.08);
}

.header h1 {
    font-size: 28px;
    margin-bottom: 8px;
}

.header p {
    color: #64748b;
}

.tabs {
    display: flex;
    gap: 12px;
    margin: 24px 0;
}

.tabs button {
    padding: 12px 20px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 999px;
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
    border-radius: 10px;
    padding: 12px 14px;
}

.error {
    color: #dc2626;
    font-size: 13px;
}

button[type="submit"] {
    width: fit-content;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    background: #2563eb;
    color: white;
    cursor: pointer;
}

button[type="submit"]:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.password-input-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #2563eb;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
}

.password-field .password-input-wrapper input {
    padding-right: 48px;
}

.password-toggle svg {
    display: block;
}

.toast {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 50;
    padding: 14px 18px;
    border-radius: 12px;
    color: white;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
}

.toast.success {
    background: #16a34a;
}

.toast.error {
    background: #dc2626;
}
</style>
