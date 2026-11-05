<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { AppButton, AppInput } from '@/components/atoms';
import { FormField, PasswordField, SurfaceCard } from '@/components/molecules';
import { csrfToken } from '@/routes';
import { store } from '@/routes/login';

const page = usePage();
const form = useForm({ username: '', password: '' });
const showUsernameWarning = ref(false);
const showPasswordWarning = ref(false);

const submit = async () => {
    form.clearErrors();
    showUsernameWarning.value = form.username.trim() === '';
    showPasswordWarning.value = form.password.trim() === '';

    if (showUsernameWarning.value || showPasswordWarning.value) {
        return;
    }

    const response = await fetch(csrfToken.url(), {
        method: 'GET',
        credentials: 'same-origin',
    });

    if (!response.ok) {
        window.location.reload();

        return;
    }

    const { token } = await response.json();

    document
        .querySelector('meta[name="csrf-token"]')
        ?.setAttribute('content', token);

    form.post(store.url());
};

function removeUsernameWhitespace(event: Event) {
    const input = event.target as HTMLInputElement;
    form.username = input.value.replace(/\s/g, '');
}
</script>

<template>
    <div class="login-page">
        <SurfaceCard class="login-card">
            <div class="login-brand">
                <img src="/favicon.svg" alt="" width="40" height="40" />
                <span>{{ page.props.name }}</span>
            </div>
            <h1 class="title">Selamat datang kembali</h1>
            <p class="login-description">
                Masuk untuk melihat jadwal dan meminjam ruangan.
            </p>

            <form @submit.prevent="submit">
                <FormField
                    label="Username"
                    for-id="username"
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
                    id="password"
                    v-model="form.password"
                    autocomplete="current-password"
                    label="Password"
                    :error="
                        form.errors.password ||
                        (showPasswordWarning ? 'Password wajib diisi' : '')
                    "
                    placeholder="Masukkan password"
                    required
                />

                <AppButton type="submit" block :disabled="form.processing">
                    {{ form.processing ? 'Sedang masuk…' : 'Masuk' }}
                </AppButton>
            </form>
            <p class="login-help">
                Butuh bantuan akun? Hubungi administrator Anda.
            </p>
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
    background: radial-gradient(
        ellipse at top,
        #dbeafe,
        var(--ui-background) 65%
    );
}
.login-card {
    width: 100%;
    max-width: 440px;
    padding: var(--ui-card-padding);
}
.login-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--ui-label-gap);
    margin-bottom: var(--ui-field-gap);
    color: var(--ui-heading);
    font-size: 18px;
    font-weight: 700;
    text-align: center;
    overflow-wrap: anywhere;
}
.title {
    text-align: center;
    margin-bottom: var(--ui-copy-gap);
    color: #1d3557;
    font-size: 26px;
    font-weight: 700;
    line-height: 1.25;
    letter-spacing: -0.025em;
}
.login-description {
    text-align: center;
    margin-bottom: var(--ui-field-gap);
    color: var(--ui-muted);
}
.login-help {
    margin-top: var(--ui-field-gap);
    padding-top: var(--ui-field-gap);
    border-top: 1px solid var(--ui-border);
    color: var(--ui-muted);
    font-size: 13px;
    text-align: center;
}
</style>
