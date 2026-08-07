<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    username: '',
    current_password: '',
    password: '',
    password_confirmation: '',
});

const activeTab = ref<'profile' | 'password'>('profile');

function saveProfile() {
    form.post('/profile', {
        preserveState: true,
        preserveScroll: true,
        only: ['errors', 'flash'],
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

                <button type="submit">Simpan Profil</button>
            </form>

            <form v-if="activeTab === 'password'" @submit.prevent="changePassword">
                <div class="field">
                    <label>Password Lama</label>
                    <input v-model="form.current_password" type="password" required />
                    <p v-if="form.errors.current_password" class="error">{{ form.errors.current_password }}</p>
                </div>

                <div class="field">
                    <label>Password Baru</label>
                    <input v-model="form.password" type="password" required />
                    <p v-if="form.errors.password" class="error">{{ form.errors.password }}</p>
                </div>

                <div class="field">
                    <label>Ulangi Password Baru</label>
                    <input v-model="form.password_confirmation" type="password" required />
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
</style>
