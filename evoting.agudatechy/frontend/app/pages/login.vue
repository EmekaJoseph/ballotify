<template>
    <div class="fade-up">
        <!-- Step 2: authenticator code -->
        <template v-if="challenge">
            <h1 class="h2 fw-800 mb-2">Two-step sign in</h1>
            <p class="text-muted mb-4">
                {{ useRecovery ? 'Enter one of your recovery codes.' : 'Open Google Authenticator and enter the 6-digit code for E-voting by Agudatechy.' }}
            </p>
            <form novalidate @submit.prevent="submitCode">
                <input id="otp" ref="otpInput" v-model.trim="code" class="form-control form-control-lg mono text-center otp mb-3"
                    :inputmode="useRecovery ? 'text' : 'numeric'" :autocomplete="useRecovery ? 'off' : 'one-time-code'"
                    :maxlength="useRecovery ? 32 : 6" :placeholder="useRecovery ? 'xxxxx-xxxxx' : '000000'" :disabled="loading" />
                <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="loading || code.length < (useRecovery ? 8 : 6)">
                    <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                    {{ loading ? 'Checking…' : 'Verify and sign in' }}
                </button>
            </form>
            <div class="d-flex justify-content-between mt-4 small">
                <button class="btn btn-link p-0 small" @click="useRecovery = !useRecovery; code = ''">
                    {{ useRecovery ? 'Use Google Authenticator code' : 'Use a recovery code' }}
                </button>
                <button class="btn btn-link p-0 small text-muted" @click="resetFlow">Start over</button>
            </div>
        </template>

        <!-- Step 1: email + password -->
        <template v-else>
            <h1 class="h2 fw-800 mb-2">Welcome back</h1>
            <p class="text-muted mb-4">Sign in to manage your events.</p>

            <div v-if="route.query.suspended" class="alert alert-danger border-0 rounded-4 small d-flex gap-2">
                This account has been suspended. Contact support if you think this is a mistake.
            </div>
            <div v-else-if="route.query.expired" class="alert alert-warning border-0 rounded-4 small d-flex gap-2">
                Your session expired. Please sign in again.
            </div>
            <div v-if="route.query.reset" class="alert alert-success border-0 rounded-4 small d-flex gap-2">
                Password updated. Sign in with your new password.
            </div>

            <form novalidate @submit.prevent="submit">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div>
                        <input id="email" v-model.trim="form.email" type="email" class="form-control form-control-lg"
                            autocomplete="email" placeholder="you@organisation.com" required :disabled="loading" />
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <PasswordInput id="password" v-model="form.password" autocomplete="current-password"
                        placeholder="Your password" required :disabled="loading" />
                    <div class="text-end mt-2">
                        <NuxtLink :to="{ path: '/forgot-password', query: form.email ? { email: form.email } : {} }" class="small fw-semibold">Forgot password?</NuxtLink>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                    {{ loading ? 'Signing in…' : 'Sign in' }}
                </button>
            </form>

            <p class="text-center text-muted mt-4 mb-0">
                New here? <NuxtLink to="/signup" class="fw-bold">Create an account</NuxtLink>
            </p>
            <p class="text-center mt-2 mb-0"><NuxtLink to="/" class="small text-muted">← Back to home</NuxtLink></p>
        </template>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

definePageMeta({ layout: 'auth', middleware: ['guest'] });
useHead({ title: 'Sign in · E-voting' });

const auth = useAuthStore();
const route = useRoute();
const loading = ref(false);
const error = ref('');
const form = reactive({ email: typeof route.query.email === 'string' ? route.query.email : '', password: '' });

const challenge = ref<string | null>(null);
useErrorAlert(error, () => (challenge.value ? 'Code not accepted' : 'Couldn’t sign you in'));
const code = ref('');
const useRecovery = ref(false);
const otpInput = ref<HTMLInputElement | null>(null);

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
        challenge.value = await auth.login(form);
        if (challenge.value) {
            await nextTick();
            otpInput.value?.focus();
        } else {
            await navigateTo(redirectTarget());
        }
    } catch (e) {
        error.value = apiErrorMessage(e, 'Could not sign in. Please try again.');
    } finally {
        loading.value = false;
    }
}

async function submitCode() {
    if (!challenge.value) return;
    error.value = '';
    loading.value = true;
    try {
        await auth.completeTwoFactor(challenge.value, code.value);
        await navigateTo(redirectTarget());
    } catch (e) {
        error.value = apiErrorMessage(e, 'That code didn’t work. Please try again.');
        code.value = '';
    } finally {
        loading.value = false;
    }
}

function resetFlow() {
    challenge.value = null;
    code.value = '';
    error.value = '';
    form.password = '';
}
</script>

<style scoped>
.otp { font-size: 1.6rem; letter-spacing: .4em; font-weight: 700; }
</style>
