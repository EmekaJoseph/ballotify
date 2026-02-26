<template>
    <div class="vote-page py-5">
        <div class="container animate-fade-in">
            <div class="row justify-content-center pt-lg-5">
                <div class="col-md-7 col-lg-6 col-xl-5">

                    <!-- Loading State (Simulated) -->
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-grow text-primary mb-4" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h4 class="fw-bold text-dark">Preparing your ballot</h4>
                        <p class="text-muted fw-medium">Securing connection to the election server...</p>
                    </div>

                    <!-- Event Flow -->
                    <div v-else-if="!ballotSubmitted">
                        <div class="glass-card overflow-hidden animate-slide-up p-0">
                            <div class="p-4 p-lg-5">
                                <div class="text-center mb-5">
                                    <div
                                        class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 small d-inline-block mb-3 fw-bold tracking-widest uppercase border border-primary border-opacity-10">
                                        <i class="bi bi-shield-check me-1"></i> Demo Ballot
                                    </div>
                                    <h2 class="fw-black mb-2 text-dark">{{ event.name }}</h2>
                                    <p class="text-muted small fw-medium">Interactive Demo Flow</p>
                                </div>

                                <div v-if="!showCodeInput && !ballotStage" class="text-center">
                                    <p class="text-muted mb-5 uppercase tracking-widest fw-bold small opacity-75">Ready
                                        to cast your vote?</p>
                                    <button @click="showCodeInput = true"
                                        class="btn btn-primary btn-lg w-100 py-3 fw-black rounded-pill shadow-sm transition-hover">
                                        Enter Voting Code <i class="bi bi-arrow-right ms-2"></i>
                                    </button>
                                    <p class="text-muted mt-4 small">Use code: <strong>DEMO-1234</strong></p>
                                </div>

                                <!-- Code Entry -->
                                <div v-else-if="showCodeInput && !ballotStage" class="animate-slide-up">
                                    <p class="text-muted mb-4 text-center fw-medium pb-2">
                                        In a real election, your unique code ensures one-time, secure participation.
                                    </p>
                                    <form @submit.prevent="handleValidateCode">
                                        <div class="mb-4">
                                            <div class="modern-input-group">
                                                <div class="input-icon">
                                                    <i class="bi bi-key text-primary"></i>
                                                </div>
                                                <input v-model="votingCode" type="text"
                                                    class="form-control modern-input" placeholder="e.g. DEMO-1234"
                                                    required :disabled="validating" autocomplete="off">
                                            </div>
                                            <div v-if="validationError"
                                                class="text-danger small mt-3 d-flex align-items-center fw-bold bg-danger bg-opacity-10 p-3 rounded-4">
                                                <i class="bi bi-exclamation-circle-fill me-2"></i> {{ validationError }}
                                            </div>
                                        </div>

                                        <button type="submit"
                                            class="btn btn-primary btn-lg w-100 py-3 fw-black rounded-pill shadow-sm transition-hover d-flex align-items-center justify-content-center"
                                            :disabled="validating">
                                            <span v-if="validating" class="spinner-grow spinner-grow-sm me-2"
                                                role="status" aria-hidden="true"></span>
                                            {{ validating ? 'VERIFYING...' : 'CONTINUE TO BALLOT' }}
                                        </button>
                                    </form>

                                    <div class="mt-4 text-center">
                                        <button @click="showCodeInput = false"
                                            class="btn btn-link text-decoration-none text-muted fw-bold back-link small uppercase tracking-wider">
                                            <i class="bi bi-arrow-left me-1"></i> Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!ballotStage"
                                class="p-3 bg-white bg-opacity-25 border-top border-white border-opacity-40 text-center">
                                <p
                                    class="small text-muted fw-bold mb-0 d-flex align-items-center justify-content-center opacity-75 uppercase tracking-tighter">
                                    <i class="bi bi-lock-fill text-success me-2"></i>
                                    Static Demo Environment
                                </p>
                            </div>
                        </div>

                        <!-- Full Ballot View -->
                        <div v-if="ballotStage" class="animate-slide-up vstack gap-4 mt-4">
                            <div class="glass-card p-4 border-primary border-opacity-10">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary">
                                        <i class="bi bi-info-circle-fill fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-black text-dark mb-1">Demo Mode Instructions</h6>
                                        <p class="text-muted small fw-medium mb-0">Experiments with the UI. In this
                                            demo,
                                            you can select candidates and see the full voting experience.</p>
                                    </div>
                                </div>
                            </div>

                            <div v-for="(cat, catIdx) in event.categories" :key="cat.id"
                                class="glass-card p-0 overflow-hidden animate-slide-up"
                                :style="{ animationDelay: `${0.1 + (catIdx * 0.1)}s` }">
                                <div
                                    class="p-3 px-4 bg-white bg-opacity-25 border-bottom border-white border-opacity-40">
                                    <h5 class="mb-0 fw-black text-dark uppercase tracking-tight fs-6">
                                        {{ cat.name }}
                                    </h5>
                                </div>
                                <div class="p-0">
                                    <div v-for="cand in getCandidatesForCat(cat.id)" :key="cand.id"
                                        class="candidate-item d-flex align-items-center justify-content-between p-3 px-4 transition-hover border-bottom border-white border-opacity-20"
                                        @click="selectSingle(cat.id, cand.id)" style="cursor: pointer;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div
                                                class="candidate-avatar-wrapper shadow-sm rounded-circle overflow-hidden border border-white p-0.5 bg-white">
                                                <div
                                                    class="candidate-avatar-img bg-light d-flex align-items-center justify-content-center text-muted">
                                                    <i class="bi bi-person-fill"></i>
                                                </div>
                                            </div>
                                            <span class="fw-bold text-dark">{{ cand.name }}</span>
                                        </div>
                                        <div class="selection-control">
                                            <div class="modern-radio"
                                                :class="{ 'checked': singleSelected[cat.id] === cand.id }">
                                                <div v-if="singleSelected[cat.id] === cand.id" class="radio-inner">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2">
                                <button @click="submitBallot"
                                    class="btn btn-primary btn-lg w-100 py-3 fw-black rounded-pill shadow transition-hover animate-slide-up"
                                    :disabled="submitting" style="animation-delay: 0.8s">
                                    <span v-if="submitting" class="spinner-grow spinner-grow-sm me-2"></span>
                                    {{ submitting ? 'SUBMITTING BALLOT...' : 'SUBMIT SECURE VOTE' }}
                                </button>
                                <div v-if="submitError"
                                    class="text-danger small mt-3 d-flex align-items-center fw-bold bg-danger bg-opacity-10 p-3 rounded-4 animate-shake">
                                    <i class="bi bi-exclamation-circle-fill me-2"></i> {{ submitError }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success State -->
                    <div v-else-if="ballotSubmitted" class="glass-card shadow-lg p-5 text-center animate-slide-up">
                        <div class="mb-4">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center shadow-inner"
                                style="width:96px;height:96px">
                                <i class="bi bi-check2-circle fs-1 pulse-animation"></i>
                            </div>
                        </div>
                        <h3 class="fw-black text-dark mb-3">Vote Recorded Successfully</h3>
                        <p class="text-muted mb-5 fw-medium">In the actual app, this vote would trigger real-time
                            updates for all dashboard users.</p>
                        <NuxtLink :to="`/demo/results`"
                            class="btn btn-dark btn-lg rounded-pill px-5 fw-bold transition-hover shadow-sm">
                            Go to Dashboard
                        </NuxtLink>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

definePageMeta({
    layout: 'public'
});

const loading = ref(true);
const showCodeInput = ref(false);
const ballotStage = ref(false);
const ballotSubmitted = ref(false);
const votingCode = ref('');
const validating = ref(false);
const validationError = ref('');
const submitting = ref(false);
const submitError = ref('');

// Hardcoded Data
const event = {
    name: 'Election Demo 2026',
    categories: [
        { id: 1, name: 'Executive Council' },
        { id: 2, name: 'Technical Committee' }
    ],
    candidates: [
        { id: 101, name: 'Dr. Sarah Mitchell', category_id: 1 },
        { id: 102, name: 'Eng. Marcus Chen', category_id: 1 },
        { id: 201, name: 'Prof. David Okafor', category_id: 2 },
        { id: 202, name: 'Linda Gustafsson', category_id: 2 }
    ]
};

const getCandidatesForCat = (catId: number) => {
    return event.candidates.filter(c => c.category_id === catId);
};

const singleSelected = ref<Record<number, number | null>>({});

const handleValidateCode = async () => {
    if (!votingCode.value.trim()) return;

    validating.value = true;
    setTimeout(() => {
        if (votingCode.value.trim().toUpperCase() === 'DEMO-1234') {
            ballotStage.value = true;
            showCodeInput.value = false;
        } else {
            validationError.value = 'Invalid code. Use DEMO-1234 for the demo.';
        }
        validating.value = false;
    }, 800);
};

const selectSingle = (categoryId: number, candidateId: number) => {
    singleSelected.value[categoryId] = candidateId;
};

const submitBallot = async () => {
    if (Object.keys(singleSelected.value).length === 0) {
        submitError.value = 'Please make at least one selection.';
        return;
    }

    submitting.value = true;
    setTimeout(() => {
        ballotSubmitted.value = true;
        submitting.value = false;
    }, 1200);
};

onMounted(() => {
    setTimeout(() => {
        loading.value = false;
    }, 600);
});
</script>

<style scoped>
.vote-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 50%, #bccad6 100%);
    background-attachment: fixed;
}

/* Glassmorphism Card */
.glass-card {
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 28px;
    box-shadow:
        0 10px 15px -3px rgba(0, 0, 0, 0.05),
        0 4px 6px -2px rgba(0, 0, 0, 0.03),
        inset 0 0 20px rgba(255, 255, 255, 0.5);
}

.shadow-inner {
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
}

/* Inputs */
.modern-input-group {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    z-index: 4;
    opacity: 0.6;
}

.modern-input {
    padding: 1.25rem 1.5rem 1.25rem 3.5rem;
    border-radius: 1.25rem;
    border: 1.5px solid rgba(255, 255, 255, 0.8);
    background-color: rgba(255, 255, 255, 0.4);
    font-size: 1.1rem;
    font-weight: 700;
    transition: all 0.3s ease;
}

.modern-input:focus {
    border-color: #0d6efd;
    background-color: #ffffff;
    box-shadow: 0 10px 15px -3px rgba(13, 110, 253, 0.1);
    outline: none;
}

/* Custom Controls */
.selection-control {
    width: 28px;
    height: 28px;
}

.modern-radio {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255, 255, 255, 0.5);
}

.modern-radio.checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.radio-inner {
    width: 12px;
    height: 12px;
    background-color: white;
    border-radius: 50%;
}

.candidate-item:hover {
    background-color: rgba(255, 255, 255, 0.4);
}

.candidate-avatar-wrapper {
    width: 44px;
    height: 44px;
}

.candidate-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-slide-up {
    opacity: 0;
    animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
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
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.pulse-animation {
    animation: iconPulse 2s infinite ease-in-out;
}

@keyframes iconPulse {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }

    100% {
        transform: scale(1);
    }
}

.animate-shake {
    animation: shake 0.5s cubic-bezier(.36, .07, .19, .97) both;
}

@keyframes shake {

    10%,
    90% {
        transform: translate3d(-1px, 0, 0);
    }

    20%,
    80% {
        transform: translate3d(2px, 0, 0);
    }

    30%,
    50%,
    70% {
        transform: translate3d(-4px, 0, 0);
    }

    40%,
    60% {
        transform: translate3d(4px, 0, 0);
    }
}

.back-link {
    transition: all 0.2s ease;
    letter-spacing: 0.05em;
}

.back-link:hover {
    color: #dc3545 !important;
    transform: translateX(-4px);
}

.uppercase {
    text-transform: uppercase;
}

.tracking-widest {
    letter-spacing: 0.15em;
}

.tracking-tighter {
    letter-spacing: -0.01em;
}

.tracking-tight {
    letter-spacing: -0.025em;
}
</style>
