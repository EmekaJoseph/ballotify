<template>
    <div>
        <div class="visibility-note mb-4">
            <i class="bi" :class="note.icon"></i>
            <span class="note-text">{{ note.text }}</span>
            <div class="actions">
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
                <button class="btn btn-primary btn-sm" :disabled="!results" title="Show full screen, e.g. on a projector" @click="togglePresent">
                    <i class="bi bi-fullscreen"></i> Present
                </button>
            </div>
        </div>
        <div v-if="loading && !results" class="d-grid gap-3">
            <div class="row g-3"><div v-for="i in 4" :key="i" class="col-6 col-lg-3"><div class="skeleton" style="height: 92px"></div></div></div>
            <div class="skeleton" style="height: 240px"></div>
        </div>

        <!-- Presentation: this element goes full screen. Works for private results too (it's your dashboard data). -->
        <div ref="stage" class="stage" :class="{ presenting }">
            <template v-if="results">
                <ResultsHeader v-if="presenting" :name="results.event.name" :status="results.event.status"
                    :ends-at="results.event.ends_at" :updated-at="updatedLabel">
                    <button class="btn btn-light-on-dark btn-sm" @click="togglePresent">
                        <i class="bi bi-fullscreen-exit"></i> Exit
                    </button>
                </ResultsHeader>
                <ResultsBoard :results="results" />
                <p v-if="presenting && event.status === 'active'" class="text-center small text-muted mt-2 mb-0">
                    <i class="bi bi-arrow-repeat"></i> Updates automatically while voting is open.
                </p>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, EventResults } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const results = ref<EventResults | null>(null);
const loading = ref(true);
const { resultsUrl } = useEventLinks(() => props.event.link_token);

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
    if (props.event.status === 'active') pollTimer = setInterval(() => load(true), 10000);
});

onMounted(() => {
    load();
    document.addEventListener('fullscreenchange', onFullscreen);
    document.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
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
</style>
