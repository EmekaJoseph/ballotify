<template>
    <div class="auth-wrapper page-bg min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="text-center mb-4 animate-slide-up">
                        <NuxtLink to="/" class="text-decoration-none">
                            <h2
                                class="display-6 fw-black text-dark mb-0 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-box-fill text-primary"></i>
                                Ballotify
                            </h2>
                        </NuxtLink>
                        <p class="text-muted mt-2 fw-medium">Create an account to start hosting elections.</p>
                    </div>

                    <div class="glass-card p-4 p-md-5 animate-slide-up" style="animation-delay: 0.1s">
                        <div v-if="errorMsg"
                            class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center gap-2 mb-4"
                            role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div>{{ errorMsg }}</div>
                        </div>

                        <form @submit.prevent="handleSignup">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark small tracking-widest uppercase">Full
                                    Name</label>
                                <input type="text" v-model="form.name"
                                    class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary"
                                    placeholder="e.g. John Doe" required :disabled="loading" />
                            </div>

                            <div class="mb-4">
                                <label
                                    class="form-label fw-bold text-dark small tracking-widest uppercase">Email</label>
                                <input type="email" v-model="form.email"
                                    class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary"
                                    placeholder="Enter your email" required :disabled="loading" />
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label
                                        class="form-label fw-bold text-dark small tracking-widest uppercase mb-1">Password</label>
                                    <div class="position-relative">
                                        <input :type="showPassword ? 'text' : 'password'" v-model="form.password"
                                            class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary pe-5"
                                            placeholder="••••••••" required :disabled="loading" />
                                        <button type="button"
                                            class="btn btn-link text-muted position-absolute end-0 top-50 translate-middle-y text-decoration-none"
                                            @click="showPassword = !showPassword" tabindex="-1">
                                            <i class="bi"
                                                :class="showPassword ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label
                                        class="form-label fw-bold text-dark small tracking-widest uppercase mb-1">Confirm
                                        Password</label>
                                    <div class="position-relative">
                                        <input type="password" v-model="form.password_confirmation"
                                            class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary"
                                            placeholder="••••••••" required :disabled="loading" />
                                    </div>
                                </div>
                            </div>

                            <button type="submit"
                                class="btn btn-primary btn-lg w-100 fw-bold rounded-pill shadow-sm transition-hover mt-2"
                                :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"
                                    aria-hidden="true"></span>
                                {{ loading ? 'Creating Account...' : 'Create Account' }}
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted small mb-0 fw-medium">
                                Already have an account?
                                <NuxtLink to="/login"
                                    class="text-primary fw-bold text-decoration-none ms-1 hover-underline">Sign in
                                </NuxtLink>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Background Blobs -->
        <div class="hero-blob bg-primary opacity-20 rounded-circle blur-3xl position-absolute pointer-events-none"
            style="top: -10%; left: -5%; width: 400px; height: 400px;"></div>
        <div class="hero-blob bg-info opacity-20 rounded-circle blur-3xl position-absolute pointer-events-none"
            style="bottom: -10%; right: -5%; width: 350px; height: 350px;"></div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

definePageMeta({
    layout: 'public',
    middleware: ['guest']
});

const authStore = useAuthStore();
const router = useRouter();

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
});

const loading = ref(false);
const showPassword = ref(false);
const errorMsg = ref('');

const handleSignup = async () => {
    if (form.password !== form.password_confirmation) {
        errorMsg.value = 'Passwords do not match.';
        return;
    }

    loading.value = true;
    errorMsg.value = '';

    try {
        await authStore.register({
            name: form.name,
            email: form.email,
            password: form.password
        });

        // redirect to dashboard
        router.push('/dashboard');
    } catch (error: any) {
        if (error.response?.data?.errors) {
            const errors: any = error.response.data.errors as Record<string, string[]>;
            const firstKey: any = Object.keys(errors)[0];
            errorMsg.value = errors[firstKey][0];
        } else if (error.response?.data?.message) {
            errorMsg.value = error.response.data.message;
        } else {
            errorMsg.value = 'An unexpected error occurred. Please try again.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.focus-ring-primary:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    background-color: #fff !important;
}

.hover-underline:hover {
    text-decoration: underline !important;
}

.pointer-events-none {
    pointer-events: none;
}
</style>
