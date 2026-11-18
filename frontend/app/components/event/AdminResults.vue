<template>
    <div>
        <div class="visibility-note mb-4">
            <i class="bi" :class="note.icon"></i>
            <span class="flex-grow-1">{{ note.text }}</span>
            <a v-if="publicNow" :href="resultsUrl" target="_blank" rel="noopener" class="btn btn-ghost btn-sm flex-shrink-0">
                <i class="bi bi-box-arrow-up-right"></i> Public page
            </a>
            <button class="btn btn-ghost btn-sm flex-shrink-0" :disabled="loading" @click="load"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            <button class="btn btn-ghost btn-sm flex-shrink-0" :disabled="!results" @click="downloadCsv"><i class="bi bi-filetype-csv"></i> CSV</button>
            <NuxtLink :to="`/report/${event.id}`" target="_blank" class="btn btn-soft btn-sm flex-shrink-0">
                <i class="bi bi-file-earmark-pdf"></i> Report / PDF
            </NuxtLink>
        </div>
        <div v-if="loading && !results" class="d-grid gap-3">
            <div class="row g-3"><div v-for="i in 4" :key="i" class="col-6 col-lg-3"><div class="skeleton" style="height: 92px"></div></div></div>
            <div class="skeleton" style="height: 240px"></div>
        </div>
        <ResultsBoard v-else-if="results" :results="results" />
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

async function load() {
    loading.value = true;
    try {
        results.value = (await adminApi.getResults(props.event.id)).data;
    } catch (e) {
        useToast().error(apiErrorMessage(e, 'Could not load results.'));
    } finally {
        loading.value = false;
    }
}
onMounted(load);
</script>

<style scoped>
.visibility-note {
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; padding: .8rem 1.1rem; border-radius: var(--radius);
    background: var(--surface); border: 1px solid var(--line); color: var(--ink-2); font-size: .9rem;
}
.visibility-note > .bi { color: var(--brand-600); font-size: 1.1rem; }
</style>
