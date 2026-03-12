<template>
    <div>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-2">
                    My Profile
                </h3>
                <p class="text-muted small mb-0 fw-medium">Manage your account settings</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="glass-card p-4 p-md-5 border-0 shadow-sm h-100">
                    <div class="d-flex align-items-center gap-4 mb-5">
                        <img v-if="authStore.user" :src="`https://api.dicebear.com/7.x/initials/svg?seed=${authStore.user?.name || 'User'}`" class="rounded-circle border border-4 border-white shadow" style="width: 100px; height: 100px;" alt="Profile">
                        <div>
                            <h4 class="fw-bold text-dark mb-1">{{ authStore.user?.name || 'Loading...' }}</h4>
                            <p class="text-muted small mb-2"><i class="bi bi-envelope me-2"></i>{{ authStore.user?.email || 'Loading...' }}</p>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 small fw-bold tracking-widest uppercase">Admin</span>
                        </div>
                    </div>

                    <form @submit.prevent="handleUpdateProfile">
                        <div v-if="successMsg" class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-check-circle-fill"></i> {{ successMsg }}
                        </div>
                        <div v-if="errorMsg" class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-exclamation-triangle-fill"></i> {{ errorMsg }}
                        </div>

                        <h6 class="fw-bold text-dark mb-3">Personal Information</h6>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small tracking-widest uppercase">Full Name</label>
                            <input type="text" v-model="form.name" class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary" required :disabled="loading" />
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small tracking-widest uppercase">Email Address</label>
                            <input type="email" v-model="form.email" class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary" required :disabled="loading" />
                        </div>

                        <hr class="border-dark border-opacity-10 my-4">

                        <h6 class="fw-bold text-dark mb-3">Change Password <span class="text-muted fw-normal small">(Leave blank to keep current)</span></h6>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small tracking-widest uppercase">New Password</label>
                            <input type="password" v-model="form.password" class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary" placeholder="••••••••" :disabled="loading" />
                        </div>

                        <button type="submit" class="btn btn-primary fw-bold rounded-pill shadow-sm transition-hover px-4" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            {{ loading ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-6">
                <!-- Placeholder for future settings -->
                <div class="glass-card p-4 p-md-5 border-0 shadow-sm h-100 opacity-50 user-select-none">
                    <h6 class="fw-bold text-dark mb-4"><i class="bi bi-shield-lock me-2"></i> Security Log</h6>
                    <div class="d-flex align-items-center justify-content-between p-3 border border-dark border-opacity-10 rounded-3 mb-3 bg-white bg-opacity-50">
                        <div>
                            <p class="fw-bold text-dark mb-0 small">Mac OS • Chrome</p>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">192.168.1.1</p>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Current Session</span>
                    </div>
                    <div class="text-center mt-5">
                       <i class="bi bi-tools display-4 text-muted opacity-50"></i>
                       <p class="text-muted small fw-medium mt-3">More settings arriving soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';

definePageMeta({
    layout: 'dashboard',
    middleware: ['auth']
});

const authStore = useAuthStore();
const loading = ref(false);
const successMsg = ref('');
const errorMsg = ref('');

const form = reactive({
    name: '',
    email: '',
    password: ''
});

onMounted(() => {
    if (authStore.user) {
        form.name = authStore.user.name;
        form.email = authStore.user.email;
    }
});

const handleUpdateProfile = async () => {
    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    // Mocking the profile update for now since the backend doesn't have the PATCH /api/me endpoint
    setTimeout(() => {
        authStore.setUser({
            ...authStore.user,
            name: form.name,
            email: form.email
        });
        successMsg.value = 'Profile updated successfully!';
        form.password = ''; // clear password field
        loading.value = false;
    }, 1000);
};
</script>

<style scoped>
.focus-ring-primary:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    background-color: #fff !important;
}
</style>
