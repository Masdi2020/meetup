<template>
    <div class="login-page">
        <div class="login-card">
            <h1 class="title">Daftar</h1>

            <form @submit.prevent="register">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        placeholder="Masukkan nama lengkap"
                        required
                    />

                    <small v-if="form.errors.name" class="error">{{ form.errors.name }}</small>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        id="username"
                        v-model="form.username"
                        type="text"
                        placeholder="Masukkan username"
                        required
                    />
                    <small v-if="form.errors.username" class="error">{{ form.errors.username }}</small>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="Masukkan email"
                        required
                    />
                    <small v-if="form.errors.email">{{ form.errors.email }}</small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="Masukkan password"
                        required
                    />
                    <small v-if="form.errors.password" class="error">{{ form.errors.password }}</small>
                </div>

                <button type="submit" class="login-btn">Daftar</button>
            </form>

            <p class="extra-text">
                Sudah punya akun?
                <a href="/login">Masuk sekarang</a>
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
});

const register = () => {
    form.post('/register');
};
</script>

<style scoped>
* {
    box-sizing: border-box;
}

.login-page {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #ffffff;
    padding: 20px;
}

.login-card {
    width: 360px;
    background: #cfe2ff;
    border-radius: 10px;
    padding: 28px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    text-align: center;
}

.title {
    font-size: 2rem;
    font-weight: 700;
    color: #1d3557;
    margin-bottom: 30px;
}

.form-group {
    text-align: left;
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #2c3e50;
}

.form-group input {
    width: 100%;
    height: 48px;
    border: 1px solid #d9d9d9;
    border-radius: 10px;
    padding: 0 15px;
    font-size: 15px;
    transition: 0.2s;
    outline: none;
}

.form-group input:focus {
    border-color: #4d8df7;
    box-shadow: 0 0 0 3px rgba(77, 141, 247, 0.15);
}

.login-btn {
    width: 140px;
    height: 44px;
    border: none;
    border-radius: 999px;
    background: #4d8df7;
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    margin-top: 12px;
}

.login-btn:hover {
    background: #397cf0;
}

.login-btn:active {
    transform: scale(0.98);
}

.extra-text {
    margin-top: 18px;
    font-size: 14px;
    color: #2c3e50;
}

.extra-text a {
    color: #1d4ed8;
    font-weight: 700;
    text-decoration: none;
}

.extra-text a:hover {
    text-decoration: underline;
}

.error {
    color: red;
    font-size: 12px;
}
</style>
