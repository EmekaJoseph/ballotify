<template>
    <div class="fade-up">
        <template v-if="!token || !email">
            <div class="icon-tile lg mb-3"><i class="bi bi-link-45deg"></i></div>
            <h1 class="h2 fw-800 mb-2">Link incomplete</h1>
            <p class="text-muted mb-4">This reset link is missing information. Request a new one and open it from the email.</p>
            <NuxtLink to="/forgot-password" class="btn btn-primary btn-lg w-100">Request a new link</NuxtLink>
        </template>

        <template v-else>
            <h1 class="h2 fw-800 mb-2">Choose a new password</h1>
            <p class="text-muted mb-4">For <b class="text-ink">{{ email }}</b>. You’ll be signed out on all other devices.</p>
            <div v-if="error" class="alert alert-danger border-0 rounded-4 small" role="alert">
                {{ error }}
                <NuxtLink v-if="expired" to="/forgot-password" class="alert-link d-block mt-1">Request a new link →</NuxtLink>
            </div>
            <form novalidate @submit.prevent="submit">
                <div class="mb-3">
                    <label for="pw" class="form-label">New password</label>
                    <PasswordInput id="pw" v-model="password" autocomplete="new-password"
                        placeholder="At least 8 characters" :disabled="loading" />
                </div>
                <div class="mb-4">
                    <label for="pw2" class="form-label">Confirm new password</label>
                    <PasswordInput id="pw2" v-model="confirm" icon="bi-shield-lock" autocomplete="new-password"
                        placeholder="Repeat password" :disabled="loading" />
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm"></span> Save new password
                </button>
            </form>
        </template>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';

definePageMeta({ layout: 'auth' });
useHead({ title: 'New password · Ballotify' });

const route = useRoute();
const token = typeof route.query.token === 'string' ? route.query.token : '';
const email = typeof route.query.email === 'string' ? route.query.email : '';
const password = ref('');
const confirm = ref('');
const loading = ref(false);
const error = ref('');
const expired = ref(false);

async function submit() {
    error.value = '';
    expired.value = false;
    if (password.value.length < 8) { error.value = 'Password must be at least 8 characters.'; return; }
    if (password.value !== confirm.value) { error.value = 'Passwords do not match.'; return; }
    loading.value = true;
    try {
        await adminApi.resetPassword({ token, email, password: password.value, password_confirmation: confirm.value });
        await navigateTo({ path: '/login', query: { reset: '1', email } });
    } catch (e) {
        error.value = apiErrorMessage(e);
        expired.value = /expired|invalid/i.test(error.value);
    } finally {
        loading.value = false;
    }
}
</script>
