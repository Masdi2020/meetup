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

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="Masukkan password"
                        required
                    />
                </div>

                <button type="submit" class="login-btn">Login</button>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue';

const form = reactive({
    username: '',
    password: '',
});

const login = () => {
    console.log(form);

    // Contoh:
    // axios.post('/login', form)
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
