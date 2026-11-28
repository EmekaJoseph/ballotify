<template>
    <div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <StatCard :label="t('res.votesCast')" :value="formatNumber(results.stats.total_votes)" icon="bi-check2-square" />
            </div>
            <div class="col-6 col-lg-3">
                <StatCard :label="t('res.turnout')" :value="turnout" icon="bi-people" tone="violet"
                    :hint="t('res.ofVoters', { voted: formatNumber(results.stats.voted), total: formatNumber(results.stats.total_voters) })" />
            </div>
            <div class="col-6 col-lg-3">
                <StatCard :label="t('res.categories')" :value="results.categories.length" icon="bi-grid" tone="amber" />
            </div>
            <div class="col-6 col-lg-3">
                <StatCard :label="t('res.candidates')" :value="results.stats.total_candidates" icon="bi-person-badge" tone="green" />
            </div>
        </div>

        <EmptyState v-if="!results.categories.length" class="card-b" icon="bi-bar-chart" :title="t('res.noCategories')"
            :text="t('res.noCategoriesText')" />

        <section v-for="cat in results.categories" :key="cat.id" class="card-b mb-4 overflow-hidden">
            <header class="card-header-b">
                <div class="min-w-0">
                    <h2 class="h5 fw-800 mb-0 text-break">{{ cat.name }}</h2>
                    <div class="small text-muted">
                        {{ tn('res.votes', catTotal(cat)) }}
                    </div>
                </div>
                <div v-if="winners(cat).length" class="pill pill-brand pill-wrap">
                    <i class="bi bi-trophy-fill"></i>
                    {{ winners(cat).length > 1 ? t('res.tied') : (closed ? t('res.winner') : t('res.leading')) }}: {{ winners(cat).map(c => c.name).join(' & ') }}
                </div>
            </header>
            <div class="p-3 p-md-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-auto d-flex justify-content-center">
                        <DonutChart :values="cat.candidates.map(c => c.votes)" :colors="palette" :size="170" :label="t('res.votesWord')" />
                    </div>
                    <div class="col">
                        <EmptyState v-if="!cat.candidates.length" icon="bi-person" :title="t('res.noCandidates')" />
                        <ol class="list-unstyled mb-0 d-grid gap-3">
                            <li v-for="(cand, i) in cat.candidates" :key="cand.id" class="d-flex align-items-center gap-3">
                                <div class="rank" :class="{ first: i === 0 && cand.votes > 0 }">{{ i + 1 }}</div>
                                <CandidateAvatar :name="cand.name" :src="cand.image_url" size="sm" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between gap-2 mb-1">
                                        <span class="min-w-0">
                                            <span class="d-block fw-semibold text-ink truncate">{{ cand.name }}</span>
                                            <span v-if="cand.description" class="d-block small text-muted truncate" :title="cand.description">{{ cand.description }}</span>
                                        </span>
                                        <span class="text-nowrap small">
                                            <b class="text-ink">{{ formatNumber(cand.votes) }}</b>
                                            <span class="text-muted"> · {{ categoryShare(cand.votes, cat) }}%</span>
                                        </span>
                                    </div>
                                    <div class="bar slim">
                                        <span :style="{ width: categoryShare(cand.votes, cat) + '%', background: palette[i % palette.length] }"></span>
                                    </div>
                                </div>
                            </li>
                        </ol>
                        <p v-if="isApproval(cat) && catTotal(cat)" class="small text-muted mt-3 mb-0"><i class="bi bi-ui-checks"></i> {{ t('res.approvalNote') }}</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import type { EventResults, ResultCategory } from '~/types';

const props = defineProps<{ results: EventResults }>();

const palette = ['#058580', '#7c5cff', '#0ea5e9', '#f59e0b', '#ec4899', '#16a34a', '#6366f1', '#ef4444'];

const closed = computed(() => props.results.event.status === 'completed');
const turnout = computed(() => {
    const { voted, total_voters } = props.results.stats;
    return total_voters ? `${percent(voted, total_voters)}%` : '—';
});

const catTotal = (cat: ResultCategory) => cat.candidates.reduce((s, c) => s + c.votes, 0);
const winners = (cat: ResultCategory) => categoryWinners(cat);
</script>

<style scoped>
.rank {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: .8rem; background: var(--surface-2); color: var(--muted); border: 1px solid var(--line);
}
.rank.first { background: var(--gold-bg); color: var(--gold-text); border-color: var(--gold-line); }
</style>
