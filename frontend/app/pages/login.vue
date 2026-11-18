<template>
    <div class="fade-up">
        <h1 class="h2 fw-800 mb-2">Welcome back</h1>
        <p class="text-muted mb-4">Sign in to manage your events.</p>

        <div v-if="route.query.expired" class="alert alert-warning border-0 rounded-4 small d-flex gap-2">
            <i class="bi bi-clock-history"></i> Your session expired. Please sign in again.
        </div>
        <div v-if="error" class="alert alert-danger border-0 rounded-4 small d-flex gap-2" role="alert">
            <i class="bi bi-exclamation-circle-fill"></i> {{ error }}
        </div>

        <form novalidate @submit.prevent="submit">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-icon">
                    <i class="bi bi-envelope"></i>
                    <input id="email" v-model.trim="form.email" type="email" class="form-control form-control-lg"
                        autocomplete="email" placeholder="you@organisation.com" required :disabled="loading" />
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-icon position-relative">
                    <i class="bi bi-lock"></i>
                    <input id="password" v-model="form.password" :type="show ? 'text' : 'password'"
                        class="form-control form-control-lg pe-5" autocomplete="current-password" placeholder="Your password"
                        required :disabled="loading" />
                    <button type="button" class="reveal" :aria-label="show ? 'Hide password' : 'Show password'" @click="show = !show">
                        <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                {{ loading ? 'Signing in…' : 'Sign in' }}
            </button>
        </form>

        <p class="text-center text-muted mt-4 mb-0">
            New to Ballotify? <NuxtLink to="/signup" class="fw-bold">Create an account</NuxtLink>
        </p>
        <p class="text-center mt-2 mb-0"><NuxtLink to="/" class="small text-muted">← Back to home</NuxtLink></p>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

definePageMeta({ layout: 'auth', middleware: ['guest'] });
useHead({ title: 'Sign in · Ballotify' });

const auth = useAuthStore();
const route = useRoute();
const loading = ref(false);
const show = ref(false);
const error = ref('');
const form = reactive({ email: '', password: '' });

/** Only follow same-site dashboard paths after login (no open redirects). */
function redirectTarget(): string {
    const r = route.query.redirect;
    return typeof r === 'string' && r.startsWith('/dashboard') ? r : '/dashboard';
}

async function submit() {
    error.value = '';
    if (!form.email || !form.password) {
        error.value = 'Enter your email and password.';
        return;
    }
    loading.value = true;
    try {
        await auth.login(form);
        await navigateTo(redirectTarget());
    } catch (e) {
        error.value = apiErrorMessage(e, 'Could not sign in. Please try again.');
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.reveal { position: absolute; right: .6rem; top: 50%; transform: translateY(-50%); border: 0; background: none; color: var(--muted); padding: .25rem .4rem; }
.reveal:hover { color: var(--ink); }
</style>
