<template>
    <div class="fade-up">
        <template v-if="sent">
            <div class="icon-tile lg green mb-3"><i class="bi bi-envelope-check"></i></div>
            <h1 class="h2 fw-800 mb-2">Check your inbox</h1>
            <p class="text-muted mb-4">
                If an account exists for <b class="text-ink">{{ email }}</b>, we’ve sent a link to choose a new password.
                It expires in 60 minutes. Don’t forget to check your spam folder.
            </p>
            <NuxtLink to="/login" class="btn btn-primary btn-lg w-100">Back to sign in</NuxtLink>
            <p class="text-center small text-muted mt-3 mb-0">
                Didn’t get it? <button class="btn btn-link btn-sm p-0 align-baseline" :disabled="loading" @click="submit">Send again</button>
            </p>
        </template>

        <template v-else>
            <h1 class="h2 fw-800 mb-2">Forgot your password?</h1>
            <p class="text-muted mb-4">Enter your account email and we’ll send you a link to reset it.</p>
            <form novalidate @submit.prevent="submit">
                <label for="email" class="form-label">Email</label>
                <div class="input-icon mb-4">
                    <i class="bi bi-envelope"></i>
                    <input id="email" v-model.trim="email" type="email" class="form-control form-control-lg" autocomplete="email"
                        placeholder="you@organisation.com" :disabled="loading" />
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="loading || !email">
                    <span v-if="loading" class="spinner-border spinner-border-sm"></span> Send reset link
                </button>
            </form>
            <p class="text-center mt-4 mb-0"><NuxtLink to="/login" class="small text-muted">← Back to sign in</NuxtLink></p>
        </template>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';

definePageMeta({ layout: 'auth', middleware: ['guest'] });
useHead({ title: 'Reset password · Ballotify' });

const route = useRoute();
const email = ref(typeof route.query.email === 'string' ? route.query.email : '');
const loading = ref(false);
const sent = ref(false);
const error = ref('');
useErrorAlert(error, 'Couldn’t send the link');

async function submit() {
    error.value = '';
    if (!/^\S+@\S+\.\S+$/.test(email.value)) { error.value = 'Enter a valid email address.'; return; }
    loading.value = true;
    try {
        await adminApi.forgotPassword(email.value);
        sent.value = true;
    } catch (e) {
        error.value = apiErrorMessage(e);
    } finally {
        loading.value = false;
    }
}
</script>
