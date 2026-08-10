<template>
    <div class="login-page">
        <div class="login-card">
            <!-- <img src="@/assets/logo.png" alt="Logo" class="logo" /> -->

            <h1 class="title">Masuk</h1>

            <form @submit.prevent="login">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        id="username"
                        v-model="form.username"
                        type="text"
                        placeholder="Masukkan username"
                        required
                    />
                </div>

                <div class="form-group password-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Masukkan password"
                            required
                        />
                        <button type="button" class="password-toggle" @click="showPassword = !showPassword" aria-label="Toggle password visibility">
                            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

                <button type="submit" class="login-btn">Login</button>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    username: '',
    password: '',
});
const showPassword = ref(false);

const login = () => {
    form.post('/login');
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

/* .logo {
  width: 95px;
  margin-bottom: 16px;
} */

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

.password-input-wrapper {
    position: relative;
}

.password-group .password-input-wrapper input {
    padding-right: 46px;
}

.password-toggle {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
    color: #4d8df7;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.password-toggle svg {
    display: block;
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
</style>
