<template>
    <div>
        <div class="d-flex align-items-center gap-3 mb-4">
            <NuxtLink to="/dashboard/events" class="btn btn-light rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-arrow-left fs-5"></i>
            </NuxtLink>
            <div>
                <h3 class="fw-black text-dark mb-1">Create New Event</h3>
                <p class="text-muted small mb-0 fw-medium">Set up a new voting event for your organization</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="glass-card p-4 p-md-5 border-0 shadow-sm">
                    <form @submit.prevent="handleCreate">
                        <div v-if="errorMsg" class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-exclamation-triangle-fill"></i> {{ errorMsg }}
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small tracking-widest uppercase">Event Title</label>
                            <input type="text" v-model="form.title" class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary" placeholder="e.g. Best Employee of the Year 2026" required :disabled="loading" />
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small tracking-widest uppercase">Description</label>
                            <textarea v-model="form.description" class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary" rows="4" placeholder="Briefly describe what this election is about..." :disabled="loading"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-bold text-dark small tracking-widest uppercase">Start Date</label>
                                <input type="date" v-model="form.start_date" class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary" required :disabled="loading" />
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-bold text-dark small tracking-widest uppercase">End Date</label>
                                <input type="date" v-model="form.end_date" class="form-control form-control-lg bg-white bg-opacity-50 border-white focus-ring-primary" required :disabled="loading" />
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill shadow-sm transition-hover px-5" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ loading ? 'Creating...' : 'Create Event' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { adminApi } from '../../../api/endpoints/admin';

definePageMeta({
    layout: 'dashboard',
    middleware: ['auth']
});

const router = useRouter();
const loading = ref(false);
const errorMsg = ref('');

const form = reactive({
    title: '',
    description: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
});

const handleCreate = async () => {
    loading.value = true;
    errorMsg.value = '';
    
    try {
        const res = await adminApi.createEvent(form);
        const newEvent = res.data;
        router.push(`/dashboard/events/${newEvent.id}`);
    } catch (error: any) {
        if (error.response?.data?.message) {
            errorMsg.value = error.response.data.message;
        } else {
            errorMsg.value = 'Failed to create event. Please check your inputs.';
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
</style>
