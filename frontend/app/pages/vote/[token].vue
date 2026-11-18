<template>
    <div>
        <!-- Loading -->
        <div v-if="stage === 'loading'" class="card-b pad">
            <div class="skeleton mb-3" style="width: 30%"></div>
            <div class="skeleton mb-2" style="height: 30px; width: 70%"></div>
            <div class="skeleton mb-4" style="width: 50%"></div>
            <div class="skeleton" style="height: 54px"></div>
        </div>

        <!-- Unavailable -->
        <div v-else-if="stage === 'unavailable'" class="card-b text-center px-4 py-5 fade-up">
            <div class="icon-tile lg mx-auto mb-3" :class="unavailable.tone"><i class="bi" :class="unavailable.icon"></i></div>
            <h1 class="h4 fw-800 mb-2">{{ unavailable.title }}</h1>
            <p class="text-muted mx-auto mb-3" style="max-width: 420px">{{ unavailable.text }}</p>
            <div v-if="unavailable.status === 'upcoming' && opensIn.label" class="countdown mx-auto mb-4">
                <div class="small-caps">Opens in</div>
                <div class="fs-3 fw-800 text-ink mono">{{ opensIn.label }}</div>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                <NuxtLink v-if="unavailable.status === 'completed'" :to="`/event/${token}/results`" class="btn btn-primary">
                    <i class="bi bi-bar-chart-line"></i> See the results
                </NuxtLink>
                <button v-if="unavailable.retry" class="btn btn-ghost" @click="load"><i class="bi bi-arrow-clockwise"></i> Try again</button>
            </div>
        </div>

        <!-- Enter code -->
        <div v-else-if="stage === 'code' && event" class="fade-up">
            <div class="intro card-b overflow-hidden mb-3">
                <div class="intro-band">
                    <span class="pill pill-active pill-live"><span class="dot"></span>Voting open</span>
                    <h1 class="h2 fw-800 text-white mt-3 mb-2 text-break">{{ event.name }}</h1>
                    <p v-if="event.description" class="intro-text mb-0" style="white-space: pre-line">{{ event.description }}</p>
                </div>
                <div class="d-flex flex-wrap gap-3 gap-sm-4 px-4 py-3 small">
                    <span class="text-muted"><i class="bi bi-grid me-1"></i>{{ event.categories.length }} categor{{ event.categories.length === 1 ? 'y' : 'ies' }}</span>
                    <span class="text-muted"><i class="bi me-1" :class="event.selection_mode === 'multiple' ? 'bi-ui-checks' : 'bi-record-circle'"></i>{{ event.selection_mode === 'multiple' ? 'Pick any number' : 'One choice per category' }}</span>
                    <span v-if="closesIn.label" class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Closes in <b class="text-ink">{{ closesIn.label }}</b></span>
                </div>
            </div>

            <form class="card-b pad" novalidate @submit.prevent="checkCode">
                <label for="code" class="h5 fw-800 d-block mb-1">Enter your voting code</label>
                <p class="text-muted small mb-3">You'll find it on your invitation or voter slip. Each code works once.</p>
                <input id="code" ref="codeInput" v-model="code" class="form-control code-input mono" :class="{ 'is-invalid': codeError }"
                    placeholder="XXXXXXXX" maxlength="64" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false"
                    inputmode="text" :disabled="checking" @input="codeError = ''" />
                <div v-if="codeError" class="field-error d-flex gap-1 align-items-start"><i class="bi bi-exclamation-circle"></i>{{ codeError }}</div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mt-3" :disabled="checking || code.trim().length < 4">
                    <span v-if="checking" class="spinner-border spinner-border-sm"></span>
                    {{ checking ? 'Checking…' : 'Continue to ballot' }} <i v-if="!checking" class="bi bi-arrow-right"></i>
                </button>
                <p class="small text-muted text-center mt-3 mb-0"><i class="bi bi-incognito"></i> Your choices are never shown with your name or code.</p>
            </form>
        </div>

        <!-- Ballot -->
        <div v-else-if="stage === 'ballot' && event" class="fade-up">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <div class="min-w-0">
                    <div class="small-caps">Official ballot</div>
                    <h1 class="h4 fw-800 mb-0 text-break">{{ event.name }}</h1>
                </div>
                <button class="btn btn-ghost btn-sm flex-shrink-0" title="Use a different code" @click="changeCode">
                    <i class="bi bi-key"></i> <span class="mono">{{ maskedCode }}</span>
                </button>
            </div>
            <BallotForm ref="ballot" :categories="event.categories" :candidates="event.candidates" :mode="event.selection_mode"
                :submitting="submitting" :error="submitError" @submit="castVote" />
        </div>

        <!-- Done -->
        <VoteSuccess v-else-if="stage === 'done'" :event-name="event?.name ?? 'this election'" :results-to="`/event/${token}/results`" />
    </div>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';
import type { Choice, EventStatus, PublicEvent } from '~/types';

definePageMeta({ layout: 'ballot' });

type Stage = 'loading' | 'unavailable' | 'code' | 'ballot' | 'done';

const route = useRoute();
const token = String(route.params.token ?? '');

const stage = ref<Stage>('loading');
const event = ref<PublicEvent | null>(null);
const code = ref('');
const codeError = ref('');
const checking = ref(false);
const submitting = ref(false);
const submitError = ref('');
const codeInput = ref<HTMLInputElement | null>(null);
const ballot = ref<{ closeReview: () => void } | null>(null);

const unavailable = reactive({
    status: '' as '' | EventStatus,
    title: '',
    text: '',
    icon: 'bi-shield-x',
    tone: '',
    retry: false,
    startsAt: null as string | null,
});

const opensIn = useCountdown(() => unavailable.startsAt);
const closesIn = useCountdown(() => event.value?.ends_at);

const normalized = computed(() => code.value.trim().toUpperCase());
const maskedCode = computed(() => (normalized.value.length > 4 ? '••••' + normalized.value.slice(-4) : normalized.value));

useHead({ title: computed(() => (event.value ? `Vote · ${event.value.name}` : 'Vote · Ballotify')) });

function showUnavailable(opts: Partial<typeof unavailable>) {
    Object.assign(unavailable, { status: '', icon: 'bi-shield-x', tone: '', retry: false, startsAt: null }, opts);
    stage.value = 'unavailable';
}

async function load() {
    stage.value = 'loading';
    try {
        event.value = (await publicApi.getEvent(token)).data;
        stage.value = 'code';
        await nextTick();
        codeInput.value?.focus();
    } catch (e) {
        const status = apiStatus(e);
        const body = (e as { response?: { data?: { status?: EventStatus; starts_at?: string | null } } }).response?.data;
        if (status === 403 && body?.status === 'upcoming') {
            showUnavailable({ status: 'upcoming', title: 'Voting hasn\'t started yet', text: `The ballot opens ${formatDate(body.starts_at ?? null, true)}. Come back then with your code.`, icon: 'bi-clock', tone: 'amber', startsAt: body.starts_at ?? null });
        } else if (status === 403) {
            showUnavailable({ status: 'completed', title: 'Voting has closed', text: 'This election is over. You can still see the results.', icon: 'bi-check2-circle', tone: 'green' });
        } else if (status === 404) {
            showUnavailable({ title: 'Ballot not found', text: 'This voting link is invalid. Check the link you were sent, or contact the organiser.' });
        } else {
            showUnavailable({ title: 'Can\'t load the ballot', text: apiErrorMessage(e), icon: 'bi-wifi-off', retry: true });
        }
    }
}

async function checkCode() {
    if (normalized.value.length < 4) return;
    checking.value = true;
    codeError.value = '';
    try {
        await publicApi.validateCode(token, normalized.value);
        stage.value = 'ballot';
        window.scrollTo({ top: 0 });
    } catch (e) {
        const status = apiStatus(e);
        if (status === 422) codeError.value = 'That code isn\'t valid or has already been used. Check it and try again.';
        else if (status === 403) await load();
        else codeError.value = apiErrorMessage(e);
    } finally {
        checking.value = false;
    }
}

function changeCode() {
    stage.value = 'code';
    submitError.value = '';
    nextTick(() => codeInput.value?.select());
}

async function castVote(choices: Choice[]) {
    submitting.value = true;
    submitError.value = '';
    try {
        await publicApi.castVote(token, { code: normalized.value, choices });
        stage.value = 'done';
        window.scrollTo({ top: 0 });
    } catch (e) {
        ballot.value?.closeReview();
        const status = apiStatus(e);
        if (status === 403) {
            submitError.value = 'Voting closed before your ballot was submitted.';
        } else {
            submitError.value = apiErrorMessage(e, 'Your vote could not be submitted. Please try again.');
        }
    } finally {
        submitting.value = false;
    }
}

onMounted(() => {
    if (token) load();
    else showUnavailable({ title: 'Ballot not found', text: 'This voting link is incomplete.' });
});
</script>

<style scoped>
.intro-band {
    padding: 1.75rem 1.5rem; position: relative;
    background:
        radial-gradient(420px 220px at 100% 0%, rgba(124, 92, 255, .45), transparent 60%),
        radial-gradient(420px 220px at 0% 100%, rgba(20, 145, 138, .55), transparent 60%),
        var(--night);
}
.intro-band .pill-active { background: rgba(74, 222, 128, .15); color: #86efac; }
.intro-text { color: rgba(255, 255, 255, .75); }
.code-input {
    font-size: clamp(1.4rem, 6vw, 1.9rem); font-weight: 700; text-align: center; letter-spacing: .25em;
    text-transform: uppercase; padding: 1rem; border-radius: 16px; border-width: 2px;
}
.code-input::placeholder { letter-spacing: .25em; color: #cbd5e1; }
.countdown { display: inline-block; padding: .85rem 1.5rem; border-radius: 16px; background: var(--warning-50); }
</style>
