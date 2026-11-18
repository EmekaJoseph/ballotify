<template>
    <div ref="root" class="results-root" :class="{ presenting }">
        <div v-if="loading" class="d-grid gap-3">
            <div class="skeleton" style="height: 140px; border-radius: 28px"></div>
            <div class="row g-3"><div v-for="i in 4" :key="i" class="col-6 col-lg-3"><div class="skeleton" style="height: 92px"></div></div></div>
            <div class="skeleton" style="height: 260px"></div>
        </div>

        <div v-else-if="error" class="card-b">
            <EmptyState :icon="notFound ? 'bi-search' : 'bi-wifi-off'" :title="notFound ? 'Results not found' : 'Couldn\'t load results'"
                :text="notFound ? 'This results link is invalid.' : error">
                <button v-if="!notFound" class="btn btn-soft" @click="fetchResults()">Try again</button>
            </EmptyState>
        </div>

        <template v-else-if="results">
            <ResultsHeader :name="results.event.name" :status="results.event.status" :ends-at="results.event.ends_at" :updated-at="updatedLabel">
                <button class="btn btn-outline-light-on-dark btn-sm" :disabled="refreshing" @click="fetchResults(true)">
                    <i class="bi bi-arrow-clockwise" :class="{ spin: refreshing }"></i> Refresh
                </button>
                <button class="btn btn-light-on-dark btn-sm" @click="togglePresent">
                    <i class="bi" :class="presenting ? 'bi-fullscreen-exit' : 'bi-fullscreen'"></i> {{ presenting ? 'Exit' : 'Present' }}
                </button>
            </ResultsHeader>

            <ResultsBoard :results="results" />

            <p class="text-center small text-muted mt-2 no-print">
                <i class="bi bi-incognito"></i> Totals only. Individual ballots are never shown.
                <template v-if="results.event.status === 'active'">
                    · {{ live ? 'Updates instantly as votes arrive.' : 'Refreshes automatically every 15 seconds.' }}
                </template>
            </p>
        </template>
    </div>
</template>

<script setup lang="ts">
import { publicApi } from '~/api/endpoints/public';
import type { EventResults } from '~/types';

definePageMeta({ layout: 'ballot', wide: true });

const POLL_MS = 15000;
const route = useRoute();
const token = String(route.params.token ?? '');
const { $echo } = useNuxtApp();

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

useHead({ title: computed(() => (results.value ? `Results · ${results.value.event.name}` : 'Results · Ballotify')) });

const updatedLabel = computed(() => {
    if (!updatedAt.value) return '';
    const s = Math.round((tick.value - updatedAt.value.getTime()) / 1000);
    if (s < 5) return 'just now';
    if (s < 60) return `${s}s ago`;
    return updatedAt.value.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
});

async function fetchResults(manual = false) {
    if (refreshing.value) return;
    refreshing.value = true;
    try {
        results.value = (await publicApi.getResults(token)).data;
        updatedAt.value = new Date();
        error.value = '';
    } catch (e) {
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

function scheduleRefresh() {
    clearTimeout(debounce);
    debounce = setTimeout(() => fetchResults(), 600); // batch bursts of votes
}

function startLive() {
    if (!results.value || results.value.event.status !== 'active') return;
    if ($echo && !channelName) {
        channelName = `event.${results.value.event.id}`;
        $echo.channel(channelName).listen('.vote.cast', scheduleRefresh);
        live.value = true;
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
    if (!token) { error.value = 'This results link is invalid.'; notFound.value = true; loading.value = false; return; }
    await fetchResults();
    startLive();
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
    clearTimeout(debounce);
    if ($echo && channelName) $echo.leave(channelName);
    document.removeEventListener('visibilitychange', onVisible);
    document.removeEventListener('fullscreenchange', onFullscreen);
});
</script>

<style scoped>
.results-root.presenting { background: var(--bg); padding: 2rem; overflow-y: auto; height: 100vh; }
.spin { animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
