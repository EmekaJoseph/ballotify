<template>
    <div class="fade-up">
        <h1 class="h2 fw-800 mb-2">Create your account</h1>
        <p class="text-muted mb-4">Set up your first election in minutes.</p>

        <div v-if="error" class="alert alert-danger border-0 rounded-4 small d-flex gap-2" role="alert">
            <i class="bi bi-exclamation-circle-fill"></i> {{ error }}
        </div>

        <form novalidate @submit.prevent="submit">
            <div class="mb-3">
                <label for="name" class="form-label">Full name</label>
                <div class="input-icon">
                    <i class="bi bi-person"></i>
                    <input id="name" v-model.trim="form.name" type="text" class="form-control form-control-lg"
                        :class="{ 'is-invalid': fieldErrors.name }" autocomplete="name" placeholder="Jane Doe" required :disabled="loading" />
                </div>
                <div v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Work email</label>
                <div class="input-icon">
                    <i class="bi bi-envelope"></i>
                    <input id="email" v-model.trim="form.email" type="email" class="form-control form-control-lg"
                        :class="{ 'is-invalid': fieldErrors.email }" autocomplete="email" placeholder="you@organisation.com" required :disabled="loading" />
                </div>
                <div v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-icon">
                    <i class="bi bi-lock"></i>
                    <input id="password" v-model="form.password" type="password" class="form-control form-control-lg"
                        :class="{ 'is-invalid': fieldErrors.password }" autocomplete="new-password" placeholder="At least 8 characters" required :disabled="loading" />
                </div>
                <div class="bar slim mt-2"><span :style="{ width: strength.pct + '%', background: strength.color }"></span></div>
                <div class="form-text">{{ strength.label }}</div>
                <div v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</div>
            </div>
            <div class="mb-4">
                <label for="confirm" class="form-label">Confirm password</label>
                <div class="input-icon">
                    <i class="bi bi-shield-lock"></i>
                    <input id="confirm" v-model="form.confirm" type="password" class="form-control form-control-lg"
                        :class="{ 'is-invalid': fieldErrors.confirm }" autocomplete="new-password" placeholder="Repeat password" required :disabled="loading" />
                </div>
                <div v-if="fieldErrors.confirm" class="field-error">{{ fieldErrors.confirm }}</div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                {{ loading ? 'Creating account…' : 'Create account' }}
            </button>
        </form>

        <p class="text-center text-muted mt-4 mb-0">
            Already have an account? <NuxtLink to="/login" class="fw-bold">Sign in</NuxtLink>
        </p>
    </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth';

definePageMeta({ layout: 'auth', middleware: ['guest'] });
useHead({ title: 'Create account · Ballotify' });

const auth = useAuthStore();
const loading = ref(false);
const error = ref('');
const fieldErrors = ref<Record<string, string>>({});
const form = reactive({ name: '', email: '', password: '', confirm: '' });

const strength = computed(() => {
    const p = form.password;
    let score = 0;
    if (p.length >= 8) score++;
    if (p.length >= 12) score++;
    if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++;
    if (/\d/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    if (!p) return { pct: 0, color: 'transparent', label: 'Use 8+ characters with a mix of letters and numbers.' };
    if (p.length < 8) return { pct: 20, color: 'var(--danger)', label: 'Too short' };
    if (score <= 2) return { pct: 45, color: 'var(--warning)', label: 'Fair' };
    if (score <= 3) return { pct: 70, color: 'var(--brand-500)', label: 'Good' };
    return { pct: 100, color: 'var(--success)', label: 'Strong' };
});

function validate(): boolean {
    const errs: Record<string, string> = {};
    if (!form.name) errs.name = 'Enter your name.';
    if (!/^\S+@\S+\.\S+$/.test(form.email)) errs.email = 'Enter a valid email address.';
    if (form.password.length < 8) errs.password = 'Password must be at least 8 characters.';
    if (form.confirm !== form.password) errs.confirm = 'Passwords do not match.';
    fieldErrors.value = errs;
    return Object.keys(errs).length === 0;
}

async function submit() {
    error.value = '';
    if (!validate()) return;
    loading.value = true;
    try {
        await auth.register({ name: form.name, email: form.email, password: form.password });
        useToast().success('Welcome to Ballotify! Create your first event.');
        await navigateTo('/dashboard/events/create');
    } catch (e) {
        fieldErrors.value = apiFieldErrors(e);
        if (!Object.keys(fieldErrors.value).length) error.value = apiErrorMessage(e);
    } finally {
        loading.value = false;
    }
}
</script>
