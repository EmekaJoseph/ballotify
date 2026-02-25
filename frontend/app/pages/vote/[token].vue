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
                            <h3 class="fw-bolder mb-3 text-dark">{{ errorTitle }}</h3>
                            <p class="text-muted mb-5 text-balance lh-lg">
                                {{ errorMessage }}
                            </p>
                            <NuxtLink to="/"
                                class="btn btn-dark w-100 py-3 fw-bold rounded-pill shadow-sm transition-hover">
                                <i class="bi bi-house-door me-2"></i>Return to Home
                            </NuxtLink>
                        </div>
                    </div>

                    <!-- Success/Event State -->
                    <div v-else-if="event && !ballotSubmitted"
                        class="card border-0 modern-card rounded-4 overflow-hidden animate-fade-in">
                        <div class="card-body p-4 p-lg-5">
                            <div class="text-center mb-5">
                                <div
                                    class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 small d-inline-block mb-3 fw-bold tracking-wider uppercase border border-primary border-opacity-10">
                                    <i class="bi bi-shield-check me-1"></i> Official Election
                                </div>
                                <h2 class="fw-bolder mb-2 text-dark">{{ event.name }}</h2>
                            </div>

                            <div v-if="!showCodeInput && !ballotStage" class="text-center">
                                <p class="text-muted mb-5 uppercase tracking-wider fw-bold">Ready to cast your vote?</p>
                                <button @click="showCodeInput = true"
                                    class="btn modern-gradient-btn text-white btn-lg w-100 py-3 fw-bold rounded-pill shadow transition-hover">
                                    Enter Voting Code <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>

                            <!-- Code Entry -->
                            <div v-else-if="showCodeInput && !ballotStage" class="animate-slide-up">
                                <p class="text-muted mb-4 text-center fw-medium">
                                    Please enter your unique voting code (e.g. <span
                                        class="fw-semibold">E1-0021</span>).
                                </p>
                                <form @submit.prevent="handleValidateCode">
                                    <div class="mb-4">
                                        <div class="modern-input-group">
                                            <div class="input-icon">
                                                <i class="bi bi-key text-primary"></i>
                                            </div>
                                            <input v-model="votingCode" type="text" class="form-control modern-input"
                                                placeholder="e.g. E1-0021" required :disabled="validating"
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

                            <!-- Ballot -->
                            <div v-else-if="ballotStage" class="animate-slide-up">
                                <div class="mb-4">
                                    <div class="alert alert-primary rounded-4 border-0">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        Select your preferred candidates below. Some elections may allow multiple
                                        choices per category.
                                    </div>
                                </div>
                                <div class="vstack gap-4">
                                    <div v-for="cat in categories" :key="cat.id"
                                        class="card border-0 shadow-sm rounded-4">
                                        <div class="card-header bg-white border-0 py-3 px-4">
                                            <h5 class="mb-0 fw-bold">
                                                {{ cat.name }}
                                            </h5>
                                        </div>
                                        <div class="card-body p-0">
                                            <ul class="list-group list-group-flush">
                                                <li v-for="cand in candidatesByCategory[cat.id] || []" :key="cand.id"
                                                    class="list-group-item d-flex align-items-center justify-content-between py-3 px-4">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img v-if="cand.image_path" :src="imageUrl(cand.image_path)"
                                                            class="rounded"
                                                            style="width:48px;height:48px;object-fit:cover" />
                                                        <span class="fw-medium">{{ cand.name }}</span>
                                                    </div>
                                                    <div>
                                                        <template v-if="selectionMode === 'multiple'">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    :id="`c-${cat.id}-${cand.id}`"
                                                                    :checked="isSelected(cat.id, cand.id)"
                                                                    @change="toggleSelection(cat.id, cand.id)">
                                                            </div>
                                                        </template>
                                                        <template v-else>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    :name="`cat-${cat.id}`"
                                                                    :id="`c-${cat.id}-${cand.id}`" :value="cand.id"
                                                                    :checked="singleSelected[cat.id] === cand.id"
                                                                    @change="selectSingle(cat.id, cand.id)">
                                                            </div>
                                                        </template>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 d-grid">
                                    <button @click="submitBallot"
                                        class="btn btn-success btn-lg rounded-pill fw-bold py-3" :disabled="submitting">
                                        <span v-if="submitting" class="spinner-grow spinner-grow-sm me-2"></span>
                                        {{ submitting ? 'Submitting Vote...' : 'Submit Vote' }}
                                    </button>
                                </div>
                                <div v-if="submitError"
                                    class="text-danger small mt-3 d-flex align-items-center fw-medium bg-danger bg-opacity-10 p-2 rounded-3">
                                    <i class="bi bi-exclamation-circle-fill me-2"></i> {{ submitError }}
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

                    <!-- Success State -->
                    <div v-else-if="ballotSubmitted"
                        class="card border-0 modern-card rounded-4 overflow-hidden animate-fade-in">
                        <div class="card-body p-5 text-center">
                            <div class="mb-4">
                                <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center"
                                    style="width:96px;height:96px">
                                    <i class="bi bi-check2-circle fs-1"></i>
                                </div>
                            </div>
                            <h3 class="fw-bolder text-dark mb-2">Your vote has been recorded</h3>
                            <p class="text-muted mb-4">Thank you for participating in this election.</p>
                            <NuxtLink to="/" class="btn btn-dark rounded-pill px-4">Return Home</NuxtLink>
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
const errorTitle = ref('Access Denied');
const errorMessage = ref('This voting link is invalid, expired, or not currently active. Please verify the link or contact your event administrator.');
const showCodeInput = ref(false);
const ballotStage = ref(false);
const ballotSubmitted = ref(false);
const votingCode = ref('');
const validating = ref(false);
const validationError = ref('');
const submitting = ref(false);
const submitError = ref('');
const categories = ref<any[]>([]);
const candidatesByCategory = ref<Record<number, any[]>>({});
const selections = ref<Record<number, Set<number>>>({});
const singleSelected = ref<Record<number, number | null>>({});
const selectionMode = ref<'single' | 'multiple'>('single');

const fetchEvent = async () => {
    try {
        loading.value = true;
        error.value = false;
        const response = await publicApi.getEventByToken(token);
        event.value = response.data;
        categories.value = event.value?.categories || [];
        const candidates = event.value?.candidates || [];
        const map: Record<number, any[]> = {};
        for (const cand of candidates) {
            if (!map[cand.category_id]) map[cand.category_id] = [];
            map[cand.category_id].push(cand);
        }
        candidatesByCategory.value = map;
        selectionMode.value = (event.value?.selection_mode === 'multiple') ? 'multiple' : 'single';
    } catch (err: any) {
        console.error('Failed to fetch event:', err);
        const status = err?.response?.status;
        if (status === 404) {
            errorTitle.value = 'Election Unavailable';
            errorMessage.value = 'This election has not started yet or has already ended.';
        }
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
            ballotStage.value = true;
            showCodeInput.value = false;
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

const isSelected = (categoryId: number, candidateId: number) => {
    return !!selections.value[categoryId]?.has(candidateId);
};

const toggleSelection = (categoryId: number, candidateId: number) => {
    if (!selections.value[categoryId]) selections.value[categoryId] = new Set<number>();
    const set = selections.value[categoryId];
    if (set.has(candidateId)) set.delete(candidateId);
    else set.add(candidateId);
    // force reactivity for Set changes
    selections.value = { ...selections.value };
};

const selectSingle = (categoryId: number, candidateId: number) => {
    singleSelected.value[categoryId] = candidateId;
    singleSelected.value = { ...singleSelected.value };
};

const submitBallot = async () => {
    submitError.value = '';
    submitting.value = true;
    try {
        const choices: { category_id: number; candidate_id: number }[] = [];
        if (selectionMode.value === 'multiple') {
            for (const [catIdStr, set] of Object.entries(selections.value)) {
                const catId = Number(catIdStr);
                set?.forEach((candId) => {
                    choices.push({ category_id: catId, candidate_id: candId });
                });
            }
        } else {
            for (const [catIdStr, candId] of Object.entries(singleSelected.value)) {
                const catId = Number(catIdStr);
                if (candId) {
                    choices.push({ category_id: catId, candidate_id: candId });
                }
            }
        }
        if (choices.length === 0) {
            submitError.value = 'Please select at least one candidate.';
            submitting.value = false;
            return;
        }
        await publicApi.castVote(token, { code: votingCode.value.trim(), choices });
        ballotSubmitted.value = true;
    } catch (err: any) {
        console.error('Vote submit failed:', err);
        submitError.value = err.response?.data?.message || 'Failed to submit your vote. Please try again.';
    } finally {
        submitting.value = false;
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
