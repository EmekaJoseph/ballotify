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
                <NuxtLink v-if="unavailable.status === 'upcoming' && nominationsOpen" :to="`/nominate/${token}`" class="btn btn-primary">
                    <i class="bi bi-megaphone"></i> Nominate someone
                </NuxtLink>
                <NuxtLink v-if="unavailable.status === 'completed' && resultsPublic" :to="`/event/${token}/results`" class="btn btn-primary">
                    <i class="bi bi-bar-chart-line"></i> See the results
                </NuxtLink>
                <button v-if="unavailable.retry" class="btn btn-ghost" @click="load"><i class="bi bi-arrow-clockwise"></i> Try again</button>
            </div>
        </div>

        <!-- Paid voting: confirming a payment after Paystack sent the voter back -->
        <div v-else-if="stage === 'confirming'" class="card-b text-center px-4 py-5">
            <span class="spinner-border text-brand mb-3"></span>
            <h1 class="h5 fw-800 mb-1">Confirming your payment…</h1>
            <p class="text-muted small mb-0">This takes a few seconds.</p>
        </div>

        <!-- Enter code (or, for paid voting, the nominee list) -->
        <div v-else-if="(stage === 'code' || stage === 'paid') && event" class="fade-up">
            <div v-if="paidNotice" class="alert border-0 rounded-4 small d-flex gap-2 mb-3" :class="paidNotice.ok ? 'alert-success' : 'alert-warning'">
                <i class="bi" :class="paidNotice.ok ? 'bi-check-circle-fill' : 'bi-info-circle'"></i> {{ paidNotice.text }}
            </div>
            <div class="intro card-b overflow-hidden mb-3">
                <div class="intro-band">
                    <img v-if="event.brand?.logo_url" :src="event.brand.logo_url" alt="" class="brand-logo mb-3" />
                    <span class="pill pill-active pill-live"><span class="dot"></span>Voting open</span>
                    <h1 class="h2 fw-800 text-white mt-3 mb-2 text-break">{{ event.name }}</h1>
                    <p v-if="event.description" class="intro-text mb-0" style="white-space: pre-line">{{ event.description }}</p>
                </div>
                <div class="d-flex flex-wrap gap-3 gap-sm-4 px-4 py-3 small">
                    <span class="text-muted"><i class="bi bi-grid me-1"></i>{{ event.categories.length }} categor{{ event.categories.length === 1 ? 'y' : 'ies' }}</span>
                    <span v-if="isPaid" class="text-muted"><i class="bi bi-cash-coin me-1"></i>₦{{ (event.vote_price_naira ?? 0).toLocaleString('en-NG') }} per vote</span>
                    <span v-else class="text-muted"><i class="bi me-1" :class="allSingle ? 'bi-record-circle' : 'bi-ui-checks'"></i>{{ allSingle ? 'One choice per category' : 'Some categories allow several picks' }}</span>
                    <span v-if="closesIn.label" class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Closes in <b class="text-ink">{{ closesIn.label }}</b></span>
                </div>
            </div>

            <PaidBallot v-if="isPaid" :token="token" :categories="event.categories" :candidates="event.candidates"
                :price="event.vote_price_naira ?? 0" :min-payment="event.min_payment_naira ?? 50" :payments-ready="!!event.payments_enabled" />
            <p v-if="isPaid && event.results_visibility === 'live'" class="text-center mt-2">
                <NuxtLink :to="`/event/${token}/results`" class="btn btn-ghost btn-sm"><i class="bi bi-bar-chart-line"></i> Live leaderboard</NuxtLink>
            </p>

            <form v-if="!isPaid" class="card-b pad" novalidate @submit.prevent="checkCode">
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

            <!-- Self-registration: the code goes to the contact the organiser has on file -->
            <div v-if="!isPaid && event.self_registration" class="card-b pad mt-3 selfreg">
                <template v-if="!selfReg.open && !selfReg.sentTo.length">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="icon-tile flex-shrink-0"><i class="bi bi-person-vcard"></i></span>
                        <span class="flex-grow-1 small text-muted">Don’t have your code? Get it with your <b class="text-ink">{{ event.member_id_label }}</b>.</span>
                        <button class="btn btn-soft btn-sm" @click="selfReg.open = true">Get my code</button>
                    </div>
                </template>
                <form v-else-if="!selfReg.sentTo.length" novalidate @submit.prevent="requestCode">
                    <label for="member-id" class="form-label">Your {{ event.member_id_label }}</label>
                    <input id="member-id" v-model.trim="selfReg.memberId" class="form-control form-control-lg mb-3" autocomplete="off" maxlength="64" :disabled="selfReg.busy" />
                    <HumanCheck ref="human" v-model="selfReg.captcha" class="mb-3" />
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-ghost" @click="selfReg.open = false">Cancel</button>
                        <button class="btn btn-primary flex-grow-1" :disabled="selfReg.busy || selfReg.memberId.length < 2">
                            <span v-if="selfReg.busy" class="spinner-border spinner-border-sm"></span> Send my code
                        </button>
                    </div>
                    <p class="small text-muted mt-2 mb-0">We’ll send it to the email or phone the organiser has on file for you.</p>
                </form>
                <div v-else class="d-flex gap-3 align-items-start">
                    <span class="icon-tile flex-shrink-0" style="background: var(--success-50); color: var(--success)"><i class="bi bi-send-check"></i></span>
                    <div class="small">
                        <div class="fw-bold text-ink mb-1">Your code is on its way</div>
                        <div class="text-muted">Sent to {{ selfReg.sentTo.join(' and ') }}. It can take a minute. Then enter it above.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ballot -->
        <div v-else-if="stage === 'ballot' && event" class="fade-up">
            <div class="mb-3">
                <div class="small-caps">Official ballot</div>
                <h1 class="h4 fw-800 mb-0 text-break">{{ event.name }}</h1>
            </div>

            <div class="voter-bar card-b mb-3">
                <span v-if="voterName" class="avatar sm" :style="{ background: colorFor(voterName) }" aria-hidden="true">{{ initials(voterName) }}</span>
                <span v-else class="icon-tile" style="width: 34px; height: 34px; font-size: 1rem"><i class="bi bi-person"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <div class="small text-muted lh-sm">Voting as</div>
                    <div class="fw-bold text-ink truncate">{{ voterName || 'Anonymous voter' }}</div>
                </div>
                <span class="code-chip d-none d-sm-inline">{{ maskedCode }}</span>
                <button class="btn btn-link btn-sm text-muted p-0 flex-shrink-0" @click="changeCode">Not you?</button>
            </div>
            <BallotForm ref="ballot" :categories="event.categories" :candidates="event.candidates"
                :submitting="submitting" :error="submitError" @submit="castVote" />
        </div>

        <!-- Done -->
        <VoteSuccess v-else-if="stage === 'done' && paidResult" :event-name="event?.name ?? ''" title="Thank you for voting!" :message="paidResult.message"
            :receipt="paidResult.reference" receipt-label="Payment reference" receipt-hint="Also emailed to you. Use it on the results page to check your votes were counted."
            :results-to="event?.results_visibility === 'private' ? '' : `/event/${token}/results`" results-label="See the leaderboard" hide-done>
            <template #actions>
                <button class="btn btn-ghost" @click="voteAgain"><i class="bi bi-heart"></i> Vote again</button>
            </template>
        </VoteSuccess>
        <VoteSuccess v-else-if="stage === 'done'" :event-name="event?.name ?? 'this election'" :voter-name="voterName" :receipt="receipt"
            :results-to="event?.results_visibility === 'private' ? '' : `/event/${token}/results`"
            :results-label="event?.results_visibility === 'live' ? 'View live results' : 'Results page (published when voting closes)'" />
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
        await alert({ title: 'Couldn’t send your code', message: apiFieldErrors(e).captcha_token ?? apiErrorMessage(e) });
    } finally {
        selfReg.busy = false;
    }
}
const checking = ref(false);
const submitting = ref(false);
const submitError = ref('');
useErrorAlert(submitError, 'Your vote wasn’t submitted');
const codeInput = ref<HTMLInputElement | null>(null);
const ballot = ref<{ closeReview: () => void } | null>(null);
/** Receipt for a counted ballot (code voting). */
const receipt = ref<string | null>(null);
/** Paid voting: outcome of the payment the voter just made. */
const paidResult = ref<{ message: string; reference: string } | null>(null);
const paidNotice = ref<{ ok: boolean; text: string } | null>(null);

const isPaid = computed(() => event.value?.voting_mode === 'paid');
const allSingle = computed(() => (event.value?.categories ?? []).every(c => c.max_choices === 1));
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

useHead({ title: computed(() => (event.value ? `Vote · ${event.value.name}` : 'Vote · Ballotify')) });

function showUnavailable(opts: Partial<typeof unavailable>) {
    Object.assign(unavailable, { status: '', icon: 'bi-shield-x', tone: '', retry: false, startsAt: null }, opts);
    stage.value = 'unavailable';
}

async function load() {
    stage.value = 'loading';
    try {
        event.value = (await publicApi.getEvent(token)).data;
        branding.value = event.value.branding !== false;
        ballotMode.value = event.value.voting_mode;
        stage.value = isPaid.value ? 'paid' : 'code';
        if (!isPaid.value) {
            await nextTick();
            codeInput.value?.focus();
        }
    } catch (e) {
        const status = apiStatus(e);
        const body = (e as { response?: { data?: { status?: EventStatus; starts_at?: string | null; results_public?: boolean; nominations_open?: boolean } } }).response?.data;
        nominationsOpen.value = !!body?.nominations_open;
        if (status === 403 && body?.status === 'upcoming') {
            showUnavailable({ status: 'upcoming', title: 'Voting hasn\'t started yet', text: `The ballot opens ${formatDate(body.starts_at ?? null, true)}. Come back then with your code.`, icon: 'bi-clock', tone: 'amber', startsAt: body.starts_at ?? null });
        } else if (status === 403) {
            resultsPublic.value = !!body?.results_public;
            showUnavailable({
                status: 'completed', title: 'Voting has closed', icon: 'bi-check2-circle', tone: 'green',
                text: resultsPublic.value ? 'This election is over. You can still see the results.' : 'This election is over. The organiser will announce the results.',
            });
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
        const { data } = await publicApi.validateCode(token, normalized.value);
        voterName.value = data.name?.trim() || null;
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
            submitError.value = 'Voting closed before your ballot was submitted.';
        } else {
            submitError.value = apiErrorMessage(e, 'Your vote could not be submitted. Please try again.');
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
        paidNotice.value = { ok: false, text: apiErrorMessage(e, 'We couldn’t confirm the payment yet. If you were charged, your votes will be added automatically.') };
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
    else showUnavailable({ title: 'Ballot not found', text: 'This voting link is incomplete.' });
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
