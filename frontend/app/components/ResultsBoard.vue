<template>
    <div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <StatCard label="Votes cast" :value="formatNumber(results.stats.total_votes)" icon="bi-check2-square" />
            </div>
            <div class="col-6 col-lg-3">
                <StatCard label="Turnout" :value="turnout" icon="bi-people" tone="violet"
                    :hint="`${formatNumber(results.stats.voted)} of ${formatNumber(results.stats.total_voters)} voters`" />
            </div>
            <div class="col-6 col-lg-3">
                <StatCard label="Categories" :value="results.categories.length" icon="bi-grid" tone="amber" />
            </div>
            <div class="col-6 col-lg-3">
                <StatCard label="Candidates" :value="results.stats.total_candidates" icon="bi-person-badge" tone="green" />
            </div>
        </div>

        <EmptyState v-if="!results.categories.length" class="card-b" icon="bi-bar-chart" title="No categories yet"
            text="Results will appear here once the ballot has categories and candidates." />

        <section v-for="cat in results.categories" :key="cat.id" class="card-b mb-4 overflow-hidden">
            <header class="card-header-b">
                <div class="min-w-0">
                    <h2 class="h5 fw-800 mb-0 text-break">{{ cat.name }}</h2>
                    <div class="small text-muted">{{ formatNumber(catTotal(cat)) }} votes</div>
                </div>
                <div v-if="cat.leader && cat.leader.votes > 0" class="pill pill-brand">
                    <i class="bi bi-trophy-fill"></i>
                    {{ isTie(cat) ? 'Tied' : (closed ? 'Winner' : 'Leading') }}: {{ isTie(cat) ? tiedNames(cat) : cat.leader.name }}
                </div>
            </header>
            <div class="p-3 p-md-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-auto d-flex justify-content-center">
                        <DonutChart :values="cat.candidates.map(c => c.votes)" :colors="palette" :size="170" />
                    </div>
                    <div class="col">
                        <EmptyState v-if="!cat.candidates.length" icon="bi-person" title="No candidates" />
                        <ol class="list-unstyled mb-0 d-grid gap-3">
                            <li v-for="(cand, i) in cat.candidates" :key="cand.id" class="d-flex align-items-center gap-3">
                                <div class="rank" :class="{ first: i === 0 && cand.votes > 0 }">{{ i + 1 }}</div>
                                <CandidateAvatar :name="cand.name" :src="cand.image_url" size="sm" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between gap-2 mb-1">
                                        <span class="fw-semibold text-ink truncate">{{ cand.name }}</span>
                                        <span class="text-nowrap small">
                                            <b class="text-ink">{{ formatNumber(cand.votes) }}</b>
                                            <span class="text-muted"> · {{ percent(cand.votes, catTotal(cat)) }}%</span>
                                        </span>
                                    </div>
                                    <div class="bar slim">
                                        <span :style="{ width: percent(cand.votes, catTotal(cat)) + '%', background: palette[i % palette.length] }"></span>
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import type { EventResults, ResultCategory } from '~/types';

const props = defineProps<{ results: EventResults }>();

const palette = ['#0e7c77', '#7c5cff', '#0ea5e9', '#f59e0b', '#ec4899', '#16a34a', '#6366f1', '#ef4444'];

const closed = computed(() => props.results.event.status === 'completed');
const turnout = computed(() => {
    const { voted, total_voters } = props.results.stats;
    return total_voters ? `${percent(voted, total_voters)}%` : '—';
});

const catTotal = (cat: ResultCategory) => cat.candidates.reduce((s, c) => s + c.votes, 0);
const topVotes = (cat: ResultCategory) => cat.candidates[0]?.votes ?? 0;
const isTie = (cat: ResultCategory) => topVotes(cat) > 0 && cat.candidates.filter(c => c.votes === topVotes(cat)).length > 1;
const tiedNames = (cat: ResultCategory) => cat.candidates.filter(c => c.votes === topVotes(cat)).map(c => c.name).join(' & ');
</script>

<style scoped>
.rank {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: .8rem; background: var(--surface-2); color: var(--muted); border: 1px solid var(--line);
}
.rank.first { background: #fff7e0; color: #b45309; border-color: #fde68a; }
</style>
