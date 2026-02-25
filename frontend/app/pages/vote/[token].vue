<template>
    <div class="vote-page py-5 py-lg-5 my-lg-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">

                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-grow text-primary mb-4" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h4 class="fw-bold text-dark">Preparing your ballot</h4>
                        <p class="text-muted fw-medium">Securing connection to the election server...</p>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="card border-0 modern-card rounded-4 overflow-hidden animate-fade-in">
                        <div class="card-body p-4 p-lg-5 text-center">
                            <div class="icon-pulse mx-auto mb-4">
                                <div
                                    class="error-icon bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center mx-auto">
                                    <i class="bi bi-shield-x fs-1"></i>
                                </div>
                            </div>
                            <h3 class="fw-bolder mb-3 text-dark">Access Denied</h3>
                            <p class="text-muted mb-5 text-balance lh-lg">
                                This voting link is invalid, expired, or has already been used to cast a ballot. Please
                                verify the link or contact your event administrator.
                            </p>
                            <NuxtLink to="/"
                                class="btn btn-dark w-100 py-3 fw-bold rounded-pill shadow-sm transition-hover">
                                <i class="bi bi-house-door me-2"></i>Return to Home
                            </NuxtLink>
                        </div>
                    </div>

                    <!-- Success/Event State -->
                    <div v-else-if="event" class="card border-0 modern-card rounded-4 overflow-hidden animate-fade-in">
                        <div class="card-body p-4 p-lg-5">
                            <div class="text-center mb-5">
                                <div
                                    class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 small d-inline-block mb-3 fw-bold tracking-wider uppercase border border-primary border-opacity-10">
                                    <i class="bi bi-shield-check me-1"></i> Official Election
                                </div>
                                <h2 class="fw-bolder mb-2 text-dark">{{ event.name }}</h2>
                            </div>

                            <div v-if="!showCodeInput" class="text-center">
                                <p class="text-muted mb-5 uppercase tracking-wider fw-bold">Ready to cast your vote?</p>
                                <button @click="showCodeInput = true"
                                    class="btn modern-gradient-btn text-white btn-lg w-100 py-3 fw-bold rounded-pill shadow transition-hover">
                                    Enter Voting Code <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>

                            <div v-else class="animate-slide-up">
                                <p class="text-muted mb-4 text-center fw-medium">Please enter your unique 16-character
                                    code.</p>
                                <form @submit.prevent="handleValidateCode">
                                    <div class="mb-4">
                                        <div class="modern-input-group">
                                            <div class="input-icon">
                                                <i class="bi bi-key text-primary"></i>
                                            </div>
                                            <input v-model="votingCode" type="text" class="form-control modern-input"
                                                placeholder="e.g. VOTE-1234-5678" required :disabled="validating"
                                                autocomplete="off">
                                        </div>
                                        <div v-if="validationError"
                                            class="text-danger small mt-3 d-flex align-items-center fw-medium bg-danger bg-opacity-10 p-2 rounded-3">
                                            <i class="bi bi-exclamation-circle-fill me-2"></i> {{ validationError }}
                                        </div>
                                    </div>

                                    <button type="submit"
                                        class="btn modern-gradient-btn text-white btn-lg w-100 py-3 fw-bold rounded-pill shadow transition-hover d-flex align-items-center justify-content-center"
                                        :disabled="validating">
                                        <span v-if="validating" class="spinner-grow spinner-grow-sm me-2" role="status"
                                            aria-hidden="true"></span>
                                        {{ validating ? 'Verifying Identity...' : 'Validate & Continue' }}
                                    </button>
                                </form>

                                <div class="mt-4 text-center">
                                    <button @click="showCodeInput = false"
                                        class="btn btn-link text-decoration-none text-muted fw-medium back-link">
                                        <i class="bi bi-arrow-left me-1"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light border-top border-opacity-10 py-3 text-center">
                            <p class="small text-muted fw-medium mb-0 d-flex align-items-center justify-content-center">
                                <i class="bi bi-lock-fill text-success me-2 opacity-75"></i>
                                Secure & Anonymous Voting
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { publicApi } from '~/api/endpoints/public';

definePageMeta({
    layout: 'public'
});

const route = useRoute();
const token = route.params.token as string;

const loading = ref(true);
const event = ref<any>(null);
const error = ref(false);
const showCodeInput = ref(false);
const votingCode = ref('');
const validating = ref(false);
const validationError = ref('');

const fetchEvent = async () => {
    try {
        loading.value = true;
        error.value = false;
        const response = await publicApi.getEventByToken(token);
        event.value = response.data;
    } catch (err) {
        console.error('Failed to fetch event:', err);
        error.value = true;
    } finally {
        loading.value = false;
    }
};

const handleValidateCode = async () => {
    if (!votingCode.value.trim()) return;

    try {
        validating.value = true;
        validationError.value = '';
        const response = await publicApi.validateCodeByToken(token, votingCode.value.trim());

        if (response.data.valid) {
            // Redirect to next step (Candidate selection)
            // For now, just alert or log
            console.log('Code valid! Proceeding to ballot...');
            alert('Code valid! In a real app, you would now see the candidates.');
        } else {
            validationError.value = 'Invalid or used voting code.';
        }
    } catch (err: any) {
        console.error('Validation failed:', err);
        validationError.value = err.response?.data?.message || 'Failed to validate code. Please try again.';
    } finally {
        validating.value = false;
    }
};

onMounted(() => {
    if (token) {
        fetchEvent();
    } else {
        error.value = true;
        loading.value = false;
    }
});
</script>

<style scoped>
.modern-card {
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.02) !important;
}

.modern-gradient-btn {
    background: linear-gradient(135deg, #0d6efd 0%, #4608ad 100%);
    border: none;
    box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2);
}

.modern-gradient-btn:hover {
    background: linear-gradient(135deg, #0b5ed7 0%, #3a068e 100%);
    box-shadow: 0 15px 25px rgba(13, 110, 253, 0.3);
}

.modern-gradient-btn:disabled {
    opacity: 0.8;
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    box-shadow: none;
    transform: none;
}

.modern-input-group {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    z-index: 4;
}

.modern-input {
    padding: 1.25rem 1.5rem 1.25rem 3.5rem;
    border-radius: 1rem;
    border: 2px solid #e9ecef;
    background-color: #f8f9fa;
    font-size: 1.1rem;
    font-weight: 600;
    transition: all 0.2s ease;
    letter-spacing: 0.05em;
}

.modern-input:focus {
    border-color: #0d6efd;
    background-color: #ffffff;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

.error-icon {
    width: 90px;
    height: 90px;
}

.icon-pulse {
    animation: pulse 2s infinite;
}

.animate-fade-in {
    animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-slide-up {
    animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.transition-hover {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.transition-hover:hover:not(:disabled) {
    transform: translateY(-4px);
}

.back-link {
    transition: color 0.2s ease;
}

.back-link:hover {
    color: #dc3545 !important;
}

.text-balance {
    text-wrap: balance;
}

.uppercase {
    text-transform: uppercase;
}

.tracking-wider {
    letter-spacing: 0.2em;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.05);
    }

    100% {
        transform: scale(1);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
