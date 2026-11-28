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
            <h1 class="h4 fw-800 mb-2">{{ unavailable.title }}</h1>
            <p class="text-muted mx-auto mb-3" style="max-width: 420px">{{ unavailable.text }}</p>
            <div v-if="unavailable.status === 'upcoming' && opensIn.label" class="countdown mx-auto mb-4">
                <div class="small-caps">{{ t('vote.opensIn') }}</div>
                <div class="fs-3 fw-800 text-ink mono">{{ opensIn.label }}</div>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                <NuxtLink v-if="unavailable.status === 'upcoming' && nominationsOpen" :to="`/nominate/${token}`" class="btn btn-primary">
                    {{ t('vote.nominate') }}
                </NuxtLink>
                <NuxtLink v-if="unavailable.status === 'completed' && resultsPublic" :to="`/event/${token}/results`" class="btn btn-primary">
                    {{ t('vote.seeResults') }}
                </NuxtLink>
                <button v-if="unavailable.retry" class="btn btn-ghost" @click="load">{{ t('common.tryAgain') }}</button>
            </div>
        </div>

        <!-- Enter code -->
        <div v-else-if="stage === 'code' && event" class="fade-up">
            <div v-if="idleClosed" class="alert alert-info border-0 rounded-4 small d-flex gap-2 mb-3" role="status">
                <span>{{ t('idle.closed') }}</span>
            </div>
            <div class="intro card-b overflow-hidden mb-3">
                <div class="intro-band">
                    <img v-if="event.brand?.logo_url" :src="event.brand.logo_url" alt="" class="brand-logo mb-3" />
                    <span class="pill pill-active pill-live"><span class="dot"></span>{{ t('vote.open') }}</span>
                    <h1 class="h2 fw-800 text-white mt-3 mb-2 text-break">{{ event.name }}</h1>
                    <p v-if="event.description" class="intro-text mb-0" style="white-space: pre-line">{{ event.description }}</p>
                </div>
                <div class="d-flex flex-wrap gap-3 gap-sm-4 px-4 py-3 small">
                    <span class="text-muted">{{ tn('vote.categories', event.categories.length) }}</span>
                    <span class="text-muted">{{ allSingle ? t('vote.oneChoice') : t('vote.severalPicks') }}</span>
                    <span v-if="closesIn.label" class="text-muted"><i class="bi bi-hourglass-split me-1"></i><Tx k="vote.closesIn"><template #time><b class="text-ink">{{ closesIn.label }}</b></template></Tx></span>
                </div>
            </div>

            <form class="card-b pad" novalidate @submit.prevent="checkCode">
                <label for="code" class="h5 fw-800 d-block mb-1">{{ t('vote.enterCode') }}</label>
                <p class="text-muted small mb-3">{{ t('vote.enterCodeHint') }}</p>
                <input id="code" ref="codeInput" v-model="code" class="form-control code-input mono" :class="{ 'is-invalid': codeError }"
                    placeholder="XXXXXXXX" maxlength="64" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false"
                    inputmode="text" :disabled="checking" @input="codeError = ''" />
                <div v-if="codeError" class="field-error d-flex gap-1 align-items-start">{{ codeError }}</div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mt-3" :disabled="checking || code.trim().length < 4">
                    <span v-if="checking" class="spinner-border spinner-border-sm"></span>
                    {{ checking ? t('vote.checking') : t('vote.continue') }}
                </button>
                <p class="small text-muted text-center mt-3 mb-0">{{ t('vote.privacy') }}</p>
            </form>

        </div>

        <!-- Ballot -->
        <div v-else-if="stage === 'ballot' && event" class="fade-up">
            <div class="mb-3">
                <div class="small-caps">{{ t('vote.officialBallot') }}</div>
                <h1 class="h4 fw-800 mb-0 text-break">{{ event.name }}</h1>
            </div>

            <div class="voter-bar card-b mb-3">
                <span v-if="voterName" class="avatar sm" :style="{ background: colorFor(voterName) }" aria-hidden="true">{{ initials(voterName) }}</span>
                <div class="min-w-0 flex-grow-1">
                    <div class="small text-muted lh-sm">{{ t('vote.votingAs') }}</div>
                    <div class="fw-bold text-ink truncate">{{ voterName || t('vote.anonymous') }}</div>
                </div>
                <span class="code-chip d-none d-sm-inline">{{ maskedCode }}</span>
                <button class="btn btn-link btn-sm text-muted p-0 flex-shrink-0" @click="changeCode">{{ t('vote.notYou') }}</button>
            </div>
            <BallotForm ref="ballot" :categories="event.categories" :candidates="event.candidates"
                :submitting="submitting" :error="submitError" @submit="castVote" />
        </div>

        <!-- Done -->
        <VoteSuccess v-else-if="stage === 'done'" :event-name="event?.name ?? ''" :voter-name="voterName" :receipt="receipt"
            :results-to="event?.results_visibility === 'private' ? '' : `/event/${token}/results`"
            :results-label="event?.results_visibility === 'live' ? t('vote.viewLive') : t('vote.resultsLater')" />
        <IdleWarning :show="idle.warning.value" :seconds="idle.secondsLeft.value" :total="IDLE_WARN_MS / 1000" @stay="idle.stay" />
    </div>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';
import type { Choice, EventStatus, PublicEvent } from '~/types';

definePageMeta({ layout: 'ballot' });
useBallotModeFor('codes');

type Stage = 'loading' | 'unavailable' | 'code' | 'ballot' | 'done';

const route = useRoute();
const router = useRouter();
const token = String(route.params.token ?? '');

const stage = ref<Stage>('loading');
const event = ref<PublicEvent | null>(null);
const code = ref('');
/** Name the organiser attached to this code (null for anonymous codes). */
const voterName = ref<string | null>(null);
/** Whether a closed event's results are public (from the API). */
const resultsPublic = ref(false);
/** Upcoming event that is collecting nominations. */
const nominationsOpen = ref(false);
const codeError = ref('');

const checking = ref(false);
const submitting = ref(false);
const submitError = ref('');
useErrorAlert(submitError, () => t('vote.notSubmitted'));
const codeInput = ref<HTMLInputElement | null>(null);
const ballot = ref<{ closeReview: () => void } | null>(null);
/** Receipt for a counted ballot. */
const receipt = ref<string | null>(null);

// ----- Idle timeout: a ballot left open (shared phone, polling-booth tablet) closes by itself.
// Nothing is submitted, so the code still works; the voter just enters it again.
// Warning after 1 minute with no tap, then 30 seconds to answer it before the ballot closes.
const IDLE_WARN_MS = 30 * 1000;
const IDLE_MS = 60 * 1000 + IDLE_WARN_MS;
const idleClosed = ref(false);
const idle = useIdleTimeout({
    idleMs: IDLE_MS,
    warnMs: IDLE_WARN_MS,
    active: () => stage.value === 'ballot' || stage.value === 'done'
        || (stage.value === 'code' && code.value.trim() !== ''),
    onTimeout: closeIdleSession,
});
function closeIdleSession() {
    code.value = '';
    voterName.value = null;
    receipt.value = null;
    codeError.value = '';
    submitError.value = '';
    stage.value = 'code';
    idleClosed.value = true;
    window.scrollTo({ top: 0 });
}
watch(code, (v) => { if (v) idleClosed.value = false; });
const allSingle = computed(() => (event.value?.categories ?? []).every(c => c.max_choices === 1));

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

useHead({ title: computed(() => `${t('vote.title')} · ${event.value?.name ?? 'E-voting'}`) });

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
        const body = (e as { response?: { data?: { status?: EventStatus; starts_at?: string | null; results_public?: boolean; nominations_open?: boolean } } }).response?.data;
        nominationsOpen.value = !!body?.nominations_open;
        if (status === 403 && body?.status === 'upcoming') {
            showUnavailable({ status: 'upcoming', title: t('vote.notStarted'), text: t('vote.opensAt', { date: formatDate(body.starts_at ?? null, true) }), icon: 'bi-clock', tone: 'amber', startsAt: body.starts_at ?? null });
        } else if (status === 403) {
            resultsPublic.value = !!body?.results_public;
            showUnavailable({
                status: 'completed', title: t('vote.closed'), icon: 'bi-check2-circle', tone: 'green',
                text: resultsPublic.value ? t('vote.closedPublic') : t('vote.closedPrivate'),
            });
        } else if (status === 404) {
            showUnavailable({ title: t('vote.notFound'), text: t('vote.invalidLink') });
        } else {
            showUnavailable({ title: t('vote.cantLoad'), text: apiErrorMessage(e), icon: 'bi-wifi-off', retry: true });
        }
    }
}

async function checkCode() {
    if (normalized.value.length < 4) return;
    checking.value = true;
    codeError.value = '';
    try {
        const { data } = await retryWhenBusy(() => publicApi.validateCode(token, normalized.value), () => { codeError.value = t('vote.busyRetrying'); });
        codeError.value = '';
        voterName.value = data.name?.trim() || null;
        stage.value = 'ballot';
        window.scrollTo({ top: 0 });
    } catch (e) {
        const status = apiStatus(e);
        if (status === 422) codeError.value = t('vote.badCode');
        else if (status === 403) await load();
        else if (status === 429 && !(e as { response?: { data?: { retry_after?: number } } }).response?.data?.retry_after) codeError.value = t('vote.busy');
        else codeError.value = apiErrorMessage(e);
    } finally {
        checking.value = false;
    }
}

function changeCode() {
    stage.value = 'code';
    voterName.value = null;
    submitError.value = '';
    nextTick(() => codeInput.value?.select());
}

async function castVote(choices: Choice[]) {
    submitting.value = true;
    submitError.value = '';
    try {
        const { data } = await retryWhenBusy(() => publicApi.castVote(token, { code: normalized.value, choices }), () => { submitError.value = t('vote.busyRetrying'); });
        submitError.value = '';
        receipt.value = data.receipt ?? null;
        stage.value = 'done';
        window.scrollTo({ top: 0 });
    } catch (e) {
        ballot.value?.closeReview();
        const status = apiStatus(e);
        if (status === 403) {
            submitError.value = t('vote.closedBeforeSubmit');
        } else if (status === 429 && !(e as { response?: { data?: { retry_after?: number } } }).response?.data?.retry_after) {
            submitError.value = t('vote.busy');
        } else {
            submitError.value = apiErrorMessage(e, t('vote.submitFailed'));
        }
    } finally {
        submitting.value = false;
    }
}

onMounted(() => {
    if (token) load();
    else showUnavailable({ title: t('vote.notFound'), text: t('vote.incompleteLink') });
});
</script>

<style scoped>
.voter-bar { display: flex; align-items: center; gap: .75rem; padding: .7rem 1rem; }
.brand-logo { display: block; max-height: 48px; max-width: 180px; object-fit: contain; border-radius: 8px; background: rgba(255, 255, 255, .92); padding: 4px 8px; }
.intro-band {
    padding: 1.75rem 1.5rem; position: relative;
    background: var(--night);
}
.intro-band .pill-active { background: rgba(74, 222, 128, .15); color: #86efac; }
.intro-text { color: rgba(255, 255, 255, .75); }
.code-input {
    font-size: clamp(1.4rem, 6vw, 1.9rem); font-weight: 700; text-align: center; letter-spacing: .25em;
    text-transform: uppercase; padding: 1rem; border-radius: 16px; border-width: 2px;
}
.code-input::placeholder { letter-spacing: .25em; color: var(--line-strong); }
.countdown { display: inline-block; padding: .85rem 1.5rem; border-radius: 16px; background: var(--warning-50); }
</style>
