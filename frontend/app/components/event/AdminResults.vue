<template>
    <div>
        <div class="visibility-note mb-4">
            <i class="bi" :class="note.icon"></i>
            <span class="note-text">{{ note.text }}</span>
            <div class="actions">
                <span v-if="live && event.status === 'active'" class="pill pill-active" title="New votes appear here by themselves">
                    <span class="dot"></span>Live
                </span>
                <a v-if="publicNow" :href="resultsUrl" target="_blank" rel="noopener" class="btn btn-ghost btn-icon btn-sm"
                    title="Open public results page" aria-label="Open public results page">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
                <button class="btn btn-ghost btn-icon btn-sm" :disabled="loading" title="Refresh results" aria-label="Refresh results" @click="load()">
                    <i class="bi bi-arrow-clockwise" :class="{ spin: loading }"></i>
                </button>
                <button class="btn btn-ghost btn-icon btn-sm" :disabled="!results" title="Download CSV" aria-label="Download CSV" @click="downloadCsv">
                    <i class="bi bi-filetype-csv"></i>
                </button>
                <NuxtLink :to="`/report/${event.id}`" target="_blank" class="btn btn-ghost btn-icon btn-sm"
                    title="Printable report / PDF" aria-label="Printable report / PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                </NuxtLink>
                <button class="btn btn-soft btn-sm d-none d-md-inline-block" :disabled="!results || !results.categories.length" title="Award-night reveal: one category at a time, winner revealed on a click" @click="startPresent('reveal')">
                    <i class="bi bi-easel"></i> Reveal
                </button>
                <!-- Projector board: desktop and tablets only (a phone screen can't show it) -->
                <button class="btn btn-primary btn-sm d-none d-md-inline-block" :disabled="!results" title="Show full screen, e.g. on a projector" @click="startPresent('board')">
                    <i class="bi bi-fullscreen"></i> Present
                </button>
            </div>
        </div>
        <!-- Certified results (after voting closes) -->
        <div v-if="event.status === 'completed' && canManage" class="cert-bar card-b mb-4">
            <span class="icon-tile" :class="certificate ? 'green' : ''"><i class="bi bi-patch-check"></i></span>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-bold text-ink">{{ certificate ? 'Results certified' : 'Certify the final results' }}</div>
                <div class="small text-muted">
                    <template v-if="certificate">Certificate <span class="mono">{{ certificate.serial }}</span> · anyone can check it with the QR code or link.</template>
                    <template v-else>Create a signed certificate with a QR code that anyone can use to check the results are genuine.</template>
                </div>
            </div>
            <template v-if="certificate">
                <a :href="`/certificate/${certificate.serial}`" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-text"></i> Certificate</a>
                <button class="btn btn-ghost btn-icon btn-sm" title="Copy verification link" aria-label="Copy verification link" @click="copyText(certificate.verify_url, 'Verification link copied')"><i class="bi bi-link-45deg"></i></button>
            </template>
            <button v-else class="btn btn-primary btn-sm" :disabled="certifying" @click="certify">
                <span v-if="certifying" class="spinner-border spinner-border-sm"></span><i v-else class="bi bi-patch-check"></i> Certify results
            </button>
        </div>

        <!-- Weighted votes / proxies / quorum (live quorum progress) -->
        <RulesBanner v-if="results?.rules" :rules="results.rules" :status="event.status" class="mb-4" />

        <div v-if="loading && !results" class="d-grid gap-3">
            <div class="row g-3"><div v-for="i in 4" :key="i" class="col-6 col-lg-3"><div class="skeleton" style="height: 92px"></div></div></div>
            <div class="skeleton" style="height: 240px"></div>
        </div>

        <!-- Presentation: this element goes full screen. Works for private results too (it's your dashboard data). -->
        <div ref="stage" class="stage" :class="{ presenting, reveal: presenting && presentMode === 'reveal', board: presenting && presentMode === 'board' }">
            <template v-if="results && presenting && presentMode === 'reveal'">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-white fw-800">{{ results.event.name }}</span>
                    <button class="btn btn-outline-light-on-dark btn-sm" @click="togglePresent"><i class="bi bi-fullscreen-exit"></i> Exit</button>
                </div>
                <RevealStage :categories="results.categories" @finish="togglePresent" />
            </template>
            <!-- Projector board: everything on one screen, refreshing while voting is open -->
            <PresentBoard v-else-if="results && presenting" :results="results" :live="live" :updated-at="updatedLabel">
                <button class="btn btn-outline-light-on-dark btn-sm" title="Exit (Esc)" @click="togglePresent">
                    <i class="bi bi-fullscreen-exit"></i> Exit
                </button>
            </PresentBoard>
            <ResultsBoard v-else-if="results" :results="results" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, EventResults, ResultCertificateInfo } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const results = ref<EventResults | null>(null);
const loading = ref(true);
const { resultsUrl } = useEventLinks(() => props.event.link_token);
const canManage = computed(() => props.event.abilities?.includes('manage') ?? true);

// ----- Certified results
const certificate = ref<ResultCertificateInfo | null>(null);
const certifying = ref(false);
async function loadCertificate() {
    if (props.event.status !== 'completed') return;
    try { certificate.value = (await adminApi.getCertificates(props.event.id)).data[0] ?? null; } catch { /* optional */ }
}
async function certify() {
    certifying.value = true;
    try {
        certificate.value = (await adminApi.issueCertificate(props.event.id)).data;
        useToast().success(`Results certified: ${certificate.value.serial}`);
    } catch (e) {
        useToast().error(apiErrorMessage(e), 'Couldn’t certify the results');
    } finally {
        certifying.value = false;
    }
}

const publicNow = computed(() => props.event.results_visibility === 'live'
    || (props.event.results_visibility === 'after_close' && props.event.status === 'completed'));

const note = computed(() => {
    switch (props.event.results_visibility) {
        case 'live': return { icon: 'bi-broadcast', text: 'These results are public and update live on the results page.' };
        case 'private': return { icon: 'bi-eye-slash', text: 'Only you can see these results. They are never published.' };
        default: return publicNow.value
            ? { icon: 'bi-unlock', text: 'Voting has closed, so these results are now public.' }
            : { icon: 'bi-lock', text: 'Only you can see these results until voting closes. Then they are published automatically.' };
    }
});

function downloadCsv() {
    if (!results.value) return;
    const stamp = new Date().toISOString().slice(0, 10);
    downloadFile(`${slugify(props.event.name)}-results-${stamp}.csv`, resultsCsv(results.value, props.event));
}

const updatedAt = ref<Date | null>(null);
const tick = ref(Date.now());
const updatedLabel = computed(() => {
    if (!updatedAt.value) return '';
    const s = Math.round((tick.value - updatedAt.value.getTime()) / 1000);
    if (s < 5) return 'just now';
    if (s < 60) return `${s}s ago`;
    return updatedAt.value.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
});

/** quiet = background refresh while presenting: no skeleton, no error toast. */
async function load(quiet = false) {
    if (!quiet) loading.value = true;
    try {
        results.value = (await adminApi.getResults(props.event.id)).data;
        updatedAt.value = new Date();
    } catch (e) {
        if (!quiet) useToast().error(apiErrorMessage(e, 'Could not load results.'));
    } finally {
        loading.value = false;
    }
}

// --- Presentation mode: full screen, refreshing while voting is open --------
const stage = ref<HTMLElement | null>(null);
const presenting = ref(false);
const presentMode = ref<'board' | 'reveal'>('board');
function startPresent(mode: 'board' | 'reveal') {
    presentMode.value = mode;
    togglePresent();
}
let pollTimer: ReturnType<typeof setInterval> | undefined;
let tickTimer: ReturnType<typeof setInterval> | undefined;

async function togglePresent() {
    if (document.fullscreenElement) {
        await document.exitFullscreen();
        return;
    }
    try {
        await stage.value?.requestFullscreen();
    } catch {
        presenting.value = !presenting.value; // no Fullscreen API (e.g. iPhone): fill the window instead
    }
}
function onFullscreen() { presenting.value = document.fullscreenElement === stage.value; }
function onKey(e: KeyboardEvent) { if (e.key === 'Escape' && presenting.value && !document.fullscreenElement) presenting.value = false; }

watch(presenting, (on) => {
    clearInterval(pollTimer);
    clearInterval(tickTimer);
    if (!on) return;
    load(true);
    tickTimer = setInterval(() => { tick.value = Date.now(); }, 5000);
    // With live updates connected the totals arrive by themselves; polling is the fallback.
    if (props.event.status === 'active') pollTimer = setInterval(() => load(true), live.value ? 60000 : 10000);
});

// --- Live tally over Pusher / Reverb (private channel: this event's team only) --------
const { $live } = useNuxtApp();
let echo: ReturnType<typeof $live.acquire> = null;
const channelName = `ballotify.admin.event.${props.event.id}`;
const live = ref(false);
let refreshTimer: ReturnType<typeof setTimeout> | undefined;
/** Many votes can land together: refetch at most about once a second. */
function scheduleRefresh() {
    if (refreshTimer) return;
    refreshTimer = setTimeout(() => { refreshTimer = undefined; load(true); }, 1000);
}
function startLive() {
    if (props.event.status === 'completed') return; // nothing more can change
    echo = $live.acquire();
    if (!echo) return;
    try {
        echo.private(channelName).listen('.vote.cast', scheduleRefresh);
        live.value = true;
    } catch (e) {
        console.warn('Live results unavailable:', e);
    }
}
function stopLive() {
    clearTimeout(refreshTimer);
    if (!echo) return;
    try { echo.leave(channelName); } catch { /* already gone */ }
    $live.release();
    echo = null;
    live.value = false;
}

onMounted(() => {
    load();
    loadCertificate();
    startLive();
    document.addEventListener('fullscreenchange', onFullscreen);
    document.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
    stopLive();
    clearInterval(pollTimer);
    clearInterval(tickTimer);
    document.removeEventListener('fullscreenchange', onFullscreen);
    document.removeEventListener('keydown', onKey);
});
</script>

<style scoped>
.visibility-note {
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; padding: .8rem 1.1rem; border-radius: var(--radius);
    background: var(--surface); border: 1px solid var(--line); color: var(--ink-2); font-size: .9rem;
}
.visibility-note > .bi { color: var(--brand-600); font-size: 1.1rem; }
.cert-bar { display: flex; align-items: center; gap: .9rem; flex-wrap: wrap; padding: .9rem 1.1rem; }
.note-text { flex: 1 1 260px; }
/* The buttons stay together and move to their own line as a group when space runs out. */
.actions { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-left: auto; }
.spin { animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
.stage.presenting {
    position: fixed; inset: 0; z-index: 2000; /* also covers the window when the Fullscreen API isn't available */
    background: var(--bg); padding: 2rem; overflow-y: auto;
}
.stage.presenting > * { max-width: 1280px; margin-left: auto; margin-right: auto; }
/* The projector board sizes itself to the screen: no padding, no scrolling (phones may scroll). */
.stage.presenting.board { padding: 0; overflow: hidden; background: #07111e; }
.stage.presenting.board > * { max-width: none; }
@media (max-width: 767.98px) { .stage.presenting.board { overflow-y: auto; } }
.stage.presenting.reveal {
    background: var(--night);
}
</style>
