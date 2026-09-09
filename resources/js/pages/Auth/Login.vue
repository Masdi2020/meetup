<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppButton from '@/components/atoms/AppButton.vue';
import AppInput from '@/components/atoms/AppInput.vue';
import FormField from '@/components/molecules/FormField.vue';
import PasswordField from '@/components/molecules/PasswordField.vue';
import SurfaceCard from '@/components/molecules/SurfaceCard.vue';

const form = useForm({ username: '', password: '' });
const showUsernameWarning = ref(false);
const showPasswordWarning = ref(false);

function login() {
    form.clearErrors();
    showUsernameWarning.value = form.username.trim() === '';
    showPasswordWarning.value = form.password.trim() === '';

    if (!showUsernameWarning.value && !showPasswordWarning.value) {
        form.post('/login');
    }
}
function removeUsernameWhitespace(event: Event) {
    const input = event.target as HTMLInputElement;
    form.username = input.value.replace(/\s/g, '');
}
</script>

<template>
    <div class="login-page">
        <SurfaceCard class="login-card">
            <h1 class="title">Masuk</h1>

            <form @submit.prevent="login">
                <FormField
                    label="Username"
                    :error="
                        form.errors.username ||
                        (showUsernameWarning ? 'Username wajib diisi' : '')
                    "
                    required
                >
                    <AppInput
                        id="username"
                        v-model="form.username"
                        type="text"
                        placeholder="Masukkan username"
                        autocapitalize="none"
                        autocorrect="off"
                        autocomplete="username"
                        :spellcheck="false"
                        @keydown.space.prevent
                        @input="removeUsernameWhitespace"
                        required
                    />
                </FormField>

                <PasswordField
                    v-model="form.password"
                    label="Password"
                    :error="
                        form.errors.password ||
                        (showPasswordWarning ? 'Password wajib diisi' : '')
                    "
                    placeholder="Masukkan password"
                    required
                />

                <AppButton type="submit" :disabled="form.processing">
                    Login
                </AppButton>
            </form>
        </SurfaceCard>
    </div>
</template>

<style scoped>
* {
    box-sizing: border-box;
}
.login-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    min-height: 100dvh;
    padding: var(--ui-page-padding);
    background: var(--ui-background);
}
.login-card {
    width: 100%;
    max-width: 400px;
    text-align: center;
}
.title {
    margin-bottom: var(--ui-gap);
    color: #1d3557;
    font-size: 2rem;
    font-weight: 700;
}
</style>
