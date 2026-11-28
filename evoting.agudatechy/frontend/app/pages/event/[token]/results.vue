<template>
    <div ref="root" class="results-root" :class="{ presenting }">
        <div v-if="loading" class="d-grid gap-3">
            <div class="skeleton" style="height: 140px; border-radius: 28px"></div>
            <div class="row g-3"><div v-for="i in 4" :key="i" class="col-6 col-lg-3"><div class="skeleton" style="height: 92px"></div></div></div>
            <div class="skeleton" style="height: 260px"></div>
        </div>

        <!-- Not published (yet) -->
        <div v-else-if="hidden" class="fade-up">
            <ResultsHeader :name="hidden.event.name" :status="hidden.event.status" :ends-at="hidden.event.ends_at" />
            <div class="card-b text-center px-4 py-5">
                <div class="icon-tile lg mx-auto mb-3" :class="hidden.visibility === 'private' ? 'violet' : ''">
                    <i class="bi" :class="hidden.visibility === 'private' ? 'bi-eye-slash' : 'bi-hourglass-split'"></i>
                </div>
                <h2 class="h4 fw-800 mb-2">{{ hidden.visibility === 'private' ? t('res.notPublic') : t('res.notYet') }}</h2>
                <p class="text-muted mx-auto mb-3" style="max-width: 440px">{{ hidden.message }}</p>
                <div v-if="hidden.visibility === 'after_close' && closesIn.label" class="countdown mx-auto">
                    <div class="small-caps">{{ t('res.in') }}</div>
                    <div class="fs-3 fw-800 text-ink mono">{{ closesIn.label }}</div>
                </div>
                <p v-if="hidden.visibility === 'after_close'" class="small text-muted mt-3 mb-0">{{ t('res.autoUpdate') }}</p>
            </div>
        </div>

        <div v-else-if="error" class="card-b">
            <EmptyState :icon="notFound ? 'bi-search' : 'bi-wifi-off'" :title="notFound ? t('res.notFound') : t('res.cantLoad')"
                :text="notFound ? t('res.invalidLink') : error">
                <button v-if="!notFound" class="btn btn-soft" @click="fetchResults()">{{ t('common.tryAgain') }}</button>
            </EmptyState>
        </div>

        <template v-else-if="results">
            <ResultsHeader :name="results.event.name" :status="results.event.status" :ends-at="results.event.ends_at" :updated-at="updatedLabel">
                <button class="btn btn-outline-light-on-dark btn-sm" :disabled="refreshing" @click="fetchResults(true)">
                    <i class="bi bi-arrow-clockwise" :class="{ spin: refreshing }"></i> {{ t('res.refresh') }}
                </button>
                <button class="btn btn-light-on-dark btn-sm" @click="togglePresent">
                    <i class="bi" :class="presenting ? 'bi-fullscreen-exit' : 'bi-fullscreen'"></i> {{ presenting ? t('res.exit') : t('res.present') }}
                </button>
            </ResultsHeader>

            <div v-if="results.integrity?.votes_reset?.length" class="integrity warn mb-2">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>{{ tn('res.votesReset', results.integrity.votes_reset.length, { date: formatDate(results.integrity.votes_reset.at(-1)?.at ?? null, true) }) }}</span>
            </div>
            <div v-if="results.integrity" class="integrity mb-4" :class="{ warn: results.integrity.changes_during_voting > 0 }">
                <i class="bi" :class="results.integrity.changes_during_voting ? 'bi-info-circle' : 'bi-shield-check'"></i>
                <span>{{ t('layout.secretBallot') }} · {{ results.integrity.changes_during_voting
                    ? tn('res.integrityChanges', results.integrity.changes_during_voting)
                    : t('res.integrityOk') }}</span>
            </div>


            <img v-if="results.event.brand?.logo_url" :src="results.event.brand.logo_url" alt="" class="brand-logo mb-3" />
            <ResultsBoard :results="results" />

            <div v-if="!presenting" class="row g-4 mt-1 no-print">
                <div class="col-lg-7">
                    <div class="card-b pad h-100">
                        <h3 class="h6 fw-800 mb-3">{{ t('res.ballotsOverTime') }}</h3>
                        <TurnoutChart :timeline="results.timeline" unit="ballots" />
                    </div>
                </div>
                <div class="col-lg-5">
                    <form class="card-b pad h-100" novalidate @submit.prevent="checkReceipt">
                        <h3 class="h6 fw-800 mb-1"><i class="bi bi-patch-check text-brand"></i> {{ t('res.checkBallot') }}</h3>
                        <p class="small text-muted mb-3">{{ t('res.checkBallotHint') }}</p>
                        <div class="d-flex gap-2">
                            <input v-model.trim="receiptCode" class="form-control mono text-uppercase" placeholder="XXXX-XXXX-XX" maxlength="40" :aria-label="t('res.receipt')" />
                            <button class="btn btn-primary" :disabled="checkingReceipt || receiptCode.length < 6">
                                <span v-if="checkingReceipt" class="spinner-border spinner-border-sm"></span><span v-else>{{ t('res.check') }}</span>
                            </button>
                        </div>
                        <div v-if="receiptResult" class="receipt-result mt-3" :class="receiptResult.valid ? 'ok' : 'bad'">
                            <i class="bi" :class="receiptResult.valid ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                            <span>{{ receiptResult.message }}<template v-if="receiptResult.valid && receiptResult.counted_at"> ({{ formatDate(receiptResult.counted_at, true) }})</template></span>
                        </div>
                    </form>
                </div>
            </div>

            <p class="text-center small text-muted mt-2 no-print">
                <i class="bi bi-incognito"></i> {{ t('res.secretNote') }}
                <template v-if="results.event.status === 'active'">
                    · {{ live ? t('res.liveNote') : t('res.pollNote') }}
                </template>
            </p>
        </template>
    </div>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';
import type { Brand, EventResults, EventStatus, ReceiptCheck } from '~/types';

definePageMeta({ layout: 'ballot', wide: true });
useBallotModeFor('codes');

const POLL_MS = 15000;
const route = useRoute();
const token = String(route.params.token ?? '');
const { $live } = useNuxtApp();
let echo: ReturnType<typeof $live.acquire> = null;

const results = ref<EventResults | null>(null);
const loading = ref(true);
const refreshing = ref(false);
const error = ref('');
const notFound = ref(false);
const updatedAt = ref<Date | null>(null);
const presenting = ref(false);
const root = ref<HTMLElement | null>(null);
const live = ref(false);
const tick = ref(Date.now());

/** Set when the organiser hasn't published results (yet). */
interface HiddenResults {
    message: string;
    visibility: 'after_close' | 'private';
    event: { name: string; status: EventStatus; starts_at: string | null; ends_at: string | null; brand?: Brand | null };
}
const hidden = ref<HiddenResults | null>(null);
const closesIn = useCountdown(() => hidden.value?.event.ends_at);

// ----- Receipt check
const receiptCode = ref('');
const checkingReceipt = ref(false);
const receiptResult = ref<ReceiptCheck | null>(null);
async function checkReceipt() {
    checkingReceipt.value = true;
    try {
        receiptResult.value = (await publicApi.checkReceipt(token, receiptCode.value)).data;
    } catch (e) {
        const body = (e as { response?: { data?: ReceiptCheck } }).response?.data;
        receiptResult.value = body?.message ? body : { valid: false, message: apiErrorMessage(e) };
    } finally {
        checkingReceipt.value = false;
    }
}

useHead({ title: computed(() => `${t('res.title')} · ${results.value?.event.name ?? hidden.value?.event.name ?? 'E-voting'}`) });

const updatedLabel = computed(() => {
    if (!updatedAt.value) return '';
    const s = Math.round((tick.value - updatedAt.value.getTime()) / 1000);
    if (s < 5) return t('res.justNow');
    if (s < 60) return t('res.secondsAgo', { n: s });
    return updatedAt.value.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
});

async function fetchResults(manual = false) {
    if (refreshing.value) return;
    refreshing.value = true;
    try {
        results.value = (await publicApi.getResults(token)).data;
        hidden.value = null;
        updatedAt.value = new Date();
        error.value = '';
    } catch (e) {
        if (apiStatus(e) === 403) {
            hidden.value = (e as { response: { data: HiddenResults } }).response.data;
            results.value = null;
            return;
        }
        notFound.value = apiStatus(e) === 404;
        // Keep showing the last good results during a transient failure.
        if (!results.value || notFound.value) error.value = apiErrorMessage(e);
        else if (manual) useToast().error(apiErrorMessage(e));
    } finally {
        refreshing.value = false;
        loading.value = false;
    }
}

// --- Live updates: Pusher when configured, polling otherwise -------------
let pollTimer: ReturnType<typeof setInterval> | undefined;
let tickTimer: ReturnType<typeof setInterval> | undefined;
let debounce: ReturnType<typeof setTimeout> | undefined;
let channelName = '';
let hiddenTimer: ReturnType<typeof setInterval> | undefined;

watch(() => closesIn.msLeft.value, (ms, prev) => {
    if (prev && prev > 0 && ms === 0) setTimeout(() => fetchResults(), 1500);
});

function scheduleRefresh() {
    clearTimeout(debounce);
    debounce = setTimeout(() => fetchResults(), 600); // batch bursts of votes
}

function startLive() {
    if (!results.value || results.value.event.status !== 'active') return;
    if (!channelName) {
        echo = $live.acquire();
        if (echo) {
            channelName = `evoting.event.${results.value.event.id}`;
            echo.channel(channelName).listen('.vote.cast', scheduleRefresh);
            live.value = true;
        }
    }
    // Polling also runs as a safety net (slower when sockets are connected).
    clearInterval(pollTimer);
    pollTimer = setInterval(() => {
        if (document.visibilityState === 'visible') fetchResults();
    }, live.value ? POLL_MS * 4 : POLL_MS);
}

function onVisible() {
    if (document.visibilityState === 'visible' && results.value?.event.status === 'active') fetchResults();
}

// --- Presentation mode ----------------------------------------------------
async function togglePresent() {
    if (!document.fullscreenElement) {
        try { await root.value?.requestFullscreen(); } catch { presenting.value = !presenting.value; return; }
    } else {
        await document.exitFullscreen();
    }
}
function onFullscreen() { presenting.value = !!document.fullscreenElement; }

onMounted(async () => {
    if (!token) { error.value = t('res.invalidLink'); notFound.value = true; loading.value = false; return; }
    await fetchResults();
    startLive();
    // Waiting for publication: check again every 30 s (and right after the countdown ends).
    hiddenTimer = setInterval(() => {
        if (hidden.value?.visibility === 'after_close' && document.visibilityState === 'visible') fetchResults();
    }, 30000);
    tickTimer = setInterval(() => { tick.value = Date.now(); }, 5000);
    document.addEventListener('visibilitychange', onVisible);
    document.addEventListener('fullscreenchange', onFullscreen);
});

watch(() => results.value?.event.status, (s, prev) => {
    if (prev === 'active' && s !== 'active') clearInterval(pollTimer);
    if (prev && prev !== 'active' && s === 'active') startLive();
});

onBeforeUnmount(() => {
    clearInterval(pollTimer);
    clearInterval(tickTimer);
    clearInterval(hiddenTimer);
    clearTimeout(debounce);
    if (echo) {
        if (channelName) echo.leave(channelName);
        $live.release();
        echo = null;
    }
    document.removeEventListener('visibilitychange', onVisible);
    document.removeEventListener('fullscreenchange', onFullscreen);
});
</script>

<style scoped>
.results-root.presenting { background: var(--bg); padding: 2rem; overflow-y: auto; height: 100vh; }
.brand-logo { display: block; max-height: 56px; max-width: 220px; object-fit: contain; }
.receipt-result { display: flex; gap: .5rem; align-items: flex-start; padding: .7rem .9rem; border-radius: 12px; font-size: .9rem; font-weight: 600; }
.receipt-result.ok { background: var(--success-50); color: var(--success); }
.receipt-result.bad { background: var(--danger-50); color: var(--danger); }
.integrity { display: flex; gap: .6rem; align-items: center; padding: .7rem 1rem; border-radius: var(--radius); background: var(--success-50); color: var(--success); font-size: .9rem; font-weight: 600; }
.integrity.warn { background: var(--warning-50); color: var(--warning-text); }
.countdown { display: inline-block; padding: .85rem 1.5rem; border-radius: 16px; background: var(--brand-50); }
.spin { animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
