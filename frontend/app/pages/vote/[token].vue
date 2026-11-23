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
                <div class="small-caps">{{ t('vote.opensIn') }}</div>
                <div class="fs-3 fw-800 text-ink mono">{{ opensIn.label }}</div>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                <NuxtLink v-if="unavailable.status === 'upcoming' && nominationsOpen" :to="`/nominate/${token}`" class="btn btn-primary">
                    <i class="bi bi-megaphone"></i> {{ t('vote.nominate') }}
                </NuxtLink>
                <NuxtLink v-if="unavailable.status === 'completed' && resultsPublic" :to="`/event/${token}/results`" class="btn btn-primary">
                    <i class="bi bi-bar-chart-line"></i> {{ t('vote.seeResults') }}
                </NuxtLink>
                <button v-if="unavailable.retry" class="btn btn-ghost" @click="load"><i class="bi bi-arrow-clockwise"></i> {{ t('common.tryAgain') }}</button>
            </div>
        </div>

        <!-- Paid voting: confirming a payment after Paystack sent the voter back -->
        <div v-else-if="stage === 'confirming'" class="card-b text-center px-4 py-5">
            <span class="spinner-border text-brand mb-3"></span>
            <h1 class="h5 fw-800 mb-1">{{ t('vote.confirming') }}</h1>
            <p class="text-muted small mb-0">{{ t('vote.confirmingHint') }}</p>
        </div>

        <!-- Enter code (or, for paid voting, the nominee list) -->
        <div v-else-if="(stage === 'code' || stage === 'paid') && event" class="fade-up">
            <div v-if="idleClosed" class="alert alert-info border-0 rounded-4 small d-flex gap-2 mb-3" role="status">
                <i class="bi bi-shield-lock"></i> <span>{{ t('idle.closed') }}</span>
            </div>
            <div v-if="paidNotice" class="alert border-0 rounded-4 small d-flex gap-2 mb-3" :class="paidNotice.ok ? 'alert-success' : 'alert-warning'">
                <i class="bi" :class="paidNotice.ok ? 'bi-check-circle-fill' : 'bi-info-circle'"></i> {{ paidNotice.text }}
            </div>
            <div class="intro card-b overflow-hidden mb-3">
                <div class="intro-band">
                    <img v-if="event.brand?.logo_url" :src="event.brand.logo_url" alt="" class="brand-logo mb-3" />
                    <span class="pill pill-active pill-live"><span class="dot"></span>{{ t('vote.open') }}</span>
                    <h1 class="h2 fw-800 text-white mt-3 mb-2 text-break">{{ event.name }}</h1>
                    <p v-if="event.description" class="intro-text mb-0" style="white-space: pre-line">{{ event.description }}</p>
                </div>
                <div class="d-flex flex-wrap gap-3 gap-sm-4 px-4 py-3 small">
                    <span class="text-muted"><i class="bi bi-grid me-1"></i>{{ tn('vote.categories', event.categories.length) }}</span>
                    <span v-if="isPaid" class="text-muted"><i class="bi bi-cash-coin me-1"></i>{{ t('vote.perVote', { price: (event.vote_price_naira ?? 0).toLocaleString('en-NG') }) }}</span>
                    <span v-else class="text-muted"><i class="bi me-1" :class="allSingle ? 'bi-record-circle' : 'bi-ui-checks'"></i>{{ allSingle ? t('vote.oneChoice') : t('vote.severalPicks') }}</span>
                    <span v-if="closesIn.label" class="text-muted"><i class="bi bi-hourglass-split me-1"></i><Tx k="vote.closesIn"><template #time><b class="text-ink">{{ closesIn.label }}</b></template></Tx></span>
                </div>
            </div>

            <PaidBallot v-if="isPaid" :token="token" :categories="event.categories" :candidates="event.candidates"
                :price="event.vote_price_naira ?? 0" :min-payment="event.min_payment_naira ?? 50" :payments-ready="!!event.payments_enabled"
                :event-name="event.name" :brand-color="event.brand?.color" />
            <p v-if="isPaid && event.results_visibility === 'live'" class="text-center mt-2">
                <NuxtLink :to="`/event/${token}/results`" class="btn btn-ghost btn-sm"><i class="bi bi-bar-chart-line"></i> {{ t('vote.leaderboard') }}</NuxtLink>
            </p>

            <form v-if="!isPaid" class="card-b pad" novalidate @submit.prevent="checkCode">
                <label for="code" class="h5 fw-800 d-block mb-1">{{ t('vote.enterCode') }}</label>
                <p class="text-muted small mb-3">{{ t('vote.enterCodeHint') }}</p>
                <input id="code" ref="codeInput" v-model="code" class="form-control code-input mono" :class="{ 'is-invalid': codeError }"
                    placeholder="XXXXXXXX" maxlength="64" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false"
                    inputmode="text" :disabled="checking" @input="codeError = ''" />
                <div v-if="codeError" class="field-error d-flex gap-1 align-items-start"><i class="bi bi-exclamation-circle"></i>{{ codeError }}</div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mt-3" :disabled="checking || code.trim().length < 4">
                    <span v-if="checking" class="spinner-border spinner-border-sm"></span>
                    {{ checking ? t('vote.checking') : t('vote.continue') }} <i v-if="!checking" class="bi bi-arrow-right"></i>
                </button>
                <p class="small text-muted text-center mt-3 mb-0"><i class="bi bi-incognito"></i> {{ t('vote.privacy') }}</p>
            </form>

            <!-- Self-registration: the code goes to the contact the organiser has on file -->
            <div v-if="!isPaid && event.self_registration" class="card-b pad mt-3 selfreg">
                <template v-if="!selfReg.open && !selfReg.sentTo.length">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="icon-tile flex-shrink-0"><i class="bi bi-person-vcard"></i></span>
                        <span class="flex-grow-1 small text-muted"><Tx k="vote.noCode"><template #label><b class="text-ink">{{ event.member_id_label }}</b></template></Tx></span>
                        <button class="btn btn-soft btn-sm" @click="selfReg.open = true">{{ t('vote.getCode') }}</button>
                    </div>
                </template>
                <form v-else-if="!selfReg.sentTo.length" novalidate @submit.prevent="requestCode">
                    <label for="member-id" class="form-label">{{ t('vote.yourId', { label: event.member_id_label ?? 'ID' }) }}</label>
                    <input id="member-id" v-model.trim="selfReg.memberId" class="form-control form-control-lg mb-3" autocomplete="off" maxlength="64" :disabled="selfReg.busy" />
                    <HumanCheck ref="human" v-model="selfReg.captcha" class="mb-3" />
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-ghost" @click="selfReg.open = false">{{ t('common.cancel') }}</button>
                        <button class="btn btn-primary flex-grow-1" :disabled="selfReg.busy || selfReg.memberId.length < 2">
                            <span v-if="selfReg.busy" class="spinner-border spinner-border-sm"></span> {{ t('vote.sendCode') }}
                        </button>
                    </div>
                    <p class="small text-muted mt-2 mb-0">{{ t('vote.sendCodeHint') }}</p>
                </form>
                <div v-else class="d-flex gap-3 align-items-start">
                    <span class="icon-tile flex-shrink-0" style="background: var(--success-50); color: var(--success)"><i class="bi bi-send-check"></i></span>
                    <div class="small">
                        <div class="fw-bold text-ink mb-1">{{ t('vote.codeOnWay') }}</div>
                        <div class="text-muted">{{ t('vote.sentTo', { to: selfReg.sentTo.join(` ${t('common.and')} `) }) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ballot -->
        <div v-else-if="stage === 'ballot' && event" class="fade-up">
            <div class="mb-3">
                <div class="small-caps">{{ t('vote.officialBallot') }}</div>
                <h1 class="h4 fw-800 mb-0 text-break">{{ event.name }}</h1>
            </div>

            <div class="voter-bar card-b mb-3">
                <span v-if="voterName" class="avatar sm" :style="{ background: colorFor(voterName) }" aria-hidden="true">{{ initials(voterName) }}</span>
                <span v-else class="icon-tile" style="width: 34px; height: 34px; font-size: 1rem"><i class="bi bi-person"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <div class="small text-muted lh-sm">{{ t('vote.votingAs') }}</div>
                    <div class="fw-bold text-ink truncate">{{ voterName || t('vote.anonymous') }}</div>
                </div>
                <span class="code-chip d-none d-sm-inline">{{ maskedCode }}</span>
                <button class="btn btn-link btn-sm text-muted p-0 flex-shrink-0" @click="changeCode">{{ t('vote.notYou') }}</button>
            </div>
            <div v-if="!myCategories.length" class="card-b pad text-center text-muted">{{ t('vote.nothingForYou') }}</div>
            <BallotForm v-else ref="ballot" :categories="myCategories" :candidates="myCandidates"
                :submitting="submitting" :error="submitError" @submit="castVote" />
        </div>

        <!-- Done -->
        <VoteSuccess v-else-if="stage === 'done' && paidResult" :event-name="event?.name ?? ''" :title="t('vote.thanksTitle')" :message="paidResult.message"
            :receipt="paidResult.reference" :receipt-label="t('vote.paymentRef')" :receipt-hint="t('vote.paymentRefHint')"
            :results-to="event?.results_visibility === 'private' ? '' : `/event/${token}/results`" :results-label="t('vote.seeLeaderboard')" hide-done>
            <template #actions>
                <button class="btn btn-ghost" @click="voteAgain"><i class="bi bi-heart"></i> {{ t('vote.voteAgain') }}</button>
            </template>
        </VoteSuccess>
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
const branding = useBallotBranding();
const ballotMode = useBallotMode();
onBeforeUnmount(() => { branding.value = true; ballotMode.value = 'codes'; }); // other pages use the defaults

type Stage = 'loading' | 'unavailable' | 'code' | 'paid' | 'confirming' | 'ballot' | 'done';

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

// ----- Self-registration
const selfReg = reactive({ open: false, memberId: '', captcha: '', busy: false, sentTo: [] as string[] });
const human = ref<{ reset: () => void } | null>(null);
const { alert } = useAlert();
async function requestCode() {
    selfReg.busy = true;
    try {
        const { data } = await publicApi.requestCode(token, { member_id: selfReg.memberId, captcha_token: selfReg.captcha || undefined });
        selfReg.sentTo = data.sent_to;
    } catch (e) {
        human.value?.reset();
        await alert({ title: t('vote.codeSendFailed'), message: apiFieldErrors(e).captcha_token ?? apiErrorMessage(e) });
    } finally {
        selfReg.busy = false;
    }
}
const checking = ref(false);
const submitting = ref(false);
const submitError = ref('');
useErrorAlert(submitError, () => t('vote.notSubmitted'));
const codeInput = ref<HTMLInputElement | null>(null);
const ballot = ref<{ closeReview: () => void } | null>(null);
/** Receipt for a counted ballot (code voting). */
const receipt = ref<string | null>(null);
/** Paid voting: outcome of the payment the voter just made. */
const paidResult = ref<{ message: string; reference: string } | null>(null);
const paidNotice = ref<{ ok: boolean; text: string } | null>(null);

const isPaid = computed(() => event.value?.voting_mode === 'paid');
const allowedCategories = ref<number[] | null>(null);
const myCategories = computed(() => (event.value?.categories ?? []).filter(c => !allowedCategories.value || allowedCategories.value.includes(c.id)));
const myCandidates = computed(() => (event.value?.candidates ?? []).filter(c => myCategories.value.some(cat => cat.id === c.category_id)));

// ----- Idle timeout: a ballot left open (shared phone, polling-booth tablet) closes by itself.
// Nothing is submitted, so the code still works; the voter just enters it again.
// Warning after 1 minute with no tap, then 30 seconds to answer it before the ballot closes.
const IDLE_WARN_MS = 30 * 1000;
const IDLE_MS = 60 * 1000 + IDLE_WARN_MS;
const idleClosed = ref(false);
const idle = useIdleTimeout({
    idleMs: IDLE_MS,
    warnMs: IDLE_WARN_MS,
    active: () => stage.value === 'ballot' || (stage.value === 'done' && !isPaid.value)
        || (stage.value === 'code' && (code.value.trim() !== '' || selfReg.memberId !== '')),
    onTimeout: closeIdleSession,
});
function closeIdleSession() {
    code.value = '';
    voterName.value = null;
    receipt.value = null;
    codeError.value = '';
    submitError.value = '';
    Object.assign(selfReg, { open: false, memberId: '', captcha: '', sentTo: [] });
    stage.value = 'code';
    idleClosed.value = true;
    window.scrollTo({ top: 0 });
}
watch(code, (v) => { if (v) idleClosed.value = false; });
const allSingle = computed(() => (event.value?.categories ?? []).every(c => c.max_choices === 1 && c.method !== 'ranked'));
useBrandTheme(() => event.value?.brand);

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

useHead({ title: computed(() => `${t('vote.title')} · ${event.value?.name ?? 'Ballotify'}`) });

function showUnavailable(opts: Partial<typeof unavailable>) {
    Object.assign(unavailable, { status: '', icon: 'bi-shield-x', tone: '', retry: false, startsAt: null }, opts);
    stage.value = 'unavailable';
}

async function load() {
    stage.value = 'loading';
    try {
        event.value = (await publicApi.getEvent(token)).data;
        setEventLanguage(event.value.language);
        branding.value = event.value.branding !== false;
        ballotMode.value = event.value.voting_mode;
        stage.value = isPaid.value ? 'paid' : 'code';
        if (!isPaid.value) {
            await nextTick();
            codeInput.value?.focus();
        }
    } catch (e) {
        const status = apiStatus(e);
        const body = (e as { response?: { data?: { status?: EventStatus; starts_at?: string | null; results_public?: boolean; nominations_open?: boolean; language?: string } } }).response?.data;
        nominationsOpen.value = !!body?.nominations_open;
        if (body?.language) setEventLanguage(body.language);
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
        const { data } = await publicApi.validateCode(token, normalized.value);
        voterName.value = data.name?.trim() || null;
        // Voter groups: the categories this code may vote in (all when the API doesn't say).
        allowedCategories.value = data.categories ?? null;
        stage.value = 'ballot';
        window.scrollTo({ top: 0 });
    } catch (e) {
        const status = apiStatus(e);
        if (status === 422) codeError.value = t('vote.badCode');
        else if (status === 403) await load();
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
        const { data } = await publicApi.castVote(token, { code: normalized.value, choices });
        receipt.value = data.receipt ?? null;
        stage.value = 'done';
        window.scrollTo({ top: 0 });
    } catch (e) {
        ballot.value?.closeReview();
        const status = apiStatus(e);
        if (status === 403) {
            submitError.value = t('vote.closedBeforeSubmit');
        } else {
            submitError.value = apiErrorMessage(e, t('vote.submitFailed'));
        }
    } finally {
        submitting.value = false;
    }
}

/** Back from Paystack with ?ref=…: confirm the payment, then show the outcome. */
async function confirmPayment(reference: string) {
    stage.value = 'confirming';
    router.replace({ query: {} });
    try {
        const { data } = await publicApi.verifyPaidVotes(reference);
        await load();
        if (data.status === 'success') {
            paidResult.value = { message: data.message, reference: data.reference };
            stage.value = 'done';
        } else {
            paidNotice.value = { ok: false, text: data.message };
        }
    } catch (e) {
        await load();
        paidNotice.value = { ok: false, text: apiErrorMessage(e, t('vote.confirmFailed')) };
    }
}

function voteAgain() {
    paidResult.value = null;
    stage.value = 'paid';
    window.scrollTo({ top: 0 });
}

onMounted(() => {
    const ref = String(route.query.ref ?? route.query.reference ?? '');
    if (token && ref) confirmPayment(ref);
    else if (token) load();
    else showUnavailable({ title: t('vote.notFound'), text: t('vote.incompleteLink') });
});
</script>

<style scoped>
.voter-bar { display: flex; align-items: center; gap: .75rem; padding: .7rem 1rem; }
.brand-logo { display: block; max-height: 48px; max-width: 180px; object-fit: contain; border-radius: 8px; background: rgba(255, 255, 255, .92); padding: 4px 8px; }
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
.code-input::placeholder { letter-spacing: .25em; color: var(--line-strong); }
.countdown { display: inline-block; padding: .85rem 1.5rem; border-radius: 16px; background: var(--warning-50); }
</style>
