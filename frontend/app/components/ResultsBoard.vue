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
                        {{ tn('res.votes', catTotal(cat)) }}<template v-if="isRanked(cat)"> ({{ t('res.firstChoices') }})</template>
                        <template v-if="cat.eligible != null"> · <i class="bi bi-people"></i> {{ t('res.eligibleLine', { voted: formatNumber(cat.ballots ?? 0), eligible: formatNumber(cat.eligible) }) }}</template>
                    </div>
                </div>
                <div v-if="isMotion(cat) && cat.motion" class="pill" :class="!cat.motion.decided ? '' : cat.motion.passed ? 'pill-active' : 'pill-danger'">
                    <i class="bi" :class="!cat.motion.decided ? 'bi-hourglass-split' : cat.motion.passed ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                    {{ !cat.motion.decided ? t('res.undecided') : cat.motion.passed ? t('res.passed') : t('res.failed') }}
                </div>
                <div v-else-if="winners(cat).length" class="pill pill-brand pill-wrap">
                    
                    {{ winners(cat).length > 1 ? t('res.tied') : (closed ? t('res.winner') : t('res.leading')) }}: {{ winners(cat).map(c => c.name).join(' & ') }}
                </div>
            </header>
            <div v-if="isMotion(cat) && cat.motion" class="p-3 p-md-4">
                <p v-if="cat.description" class="motion-text mb-3">{{ cat.description }}</p>
                <div class="d-grid gap-3">
                    <div v-for="c in cat.candidates" :key="c.id" class="motion-row">
                        <div class="d-flex justify-content-between gap-2 mb-1">
                            <span class="fw-semibold text-ink"><i class="bi me-1" :class="MOTION_ICON[c.motion_choice ?? 'abstain']"></i>{{ t(`motion.${c.motion_choice ?? 'abstain'}`) }}</span>
                            <span class="small text-nowrap"><b class="text-ink">{{ formatNumber(c.votes) }}</b>
                                <span v-if="c.motion_choice !== 'abstain'" class="text-muted"> · {{ percent(c.votes, cat.motion.yes + cat.motion.no) }}%</span></span>
                        </div>
                        <div class="bar slim"><span :class="c.motion_choice" :style="{ width: (c.motion_choice === 'abstain' ? percent(c.votes, catTotal(cat)) : percent(c.votes, cat.motion.yes + cat.motion.no)) + '%' }"></span></div>
                    </div>
                </div>
                <p class="small text-muted mt-3 mb-0">
                    {{ t(`res.rule.${cat.motion.rule}`) }} <template v-if="cat.motion.abstain">{{ t('res.abstainNote', { n: formatNumber(cat.motion.abstain) }) }}</template>
                </p>
            </div>
            <div v-else class="p-3 p-md-4">
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
                        <p v-if="isApproval(cat) && !paid && catTotal(cat)" class="small text-muted mt-3 mb-0"><i v-if="adminIcons" class="bi bi-ui-checks"></i> {{ t('res.approvalNote') }}</p>
                    </div>
                </div>
                <RoundsTable v-if="isRanked(cat)" :category="cat" />
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import type { EventResults, ResultCategory } from '~/types';

const adminIcons = useAdminIcons();

const props = defineProps<{ results: EventResults }>();

const palette = ['#0e7c77', '#7c5cff', '#0ea5e9', '#f59e0b', '#ec4899', '#16a34a', '#6366f1', '#ef4444'];

const closed = computed(() => props.results.event.status === 'completed');
const turnout = computed(() => {
    const { voted, total_voters } = props.results.stats;
    return total_voters ? `${percent(voted, total_voters)}%` : '—';
});

const paid = computed(() => props.results.event.voting_mode === 'paid');
const catTotal = (cat: ResultCategory) => cat.candidates.reduce((s, c) => s + c.votes, 0);
const winners = (cat: ResultCategory) => categoryWinners(cat);
const MOTION_ICON = { yes: 'bi-check-lg text-success', no: 'bi-x-lg text-danger', abstain: 'bi-dash-lg text-muted' } as const;
</script>

<style scoped>
.motion-text { white-space: pre-line; color: var(--ink); max-width: 760px; }
.motion-row .bar span.yes { background: #16a34a; }
.motion-row .bar span.no { background: #dc2626; }
.motion-row .bar span.abstain { background: var(--line-strong); }
.pill-danger { background: #fef3f2; color: #b42318; }
.rank {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: .8rem; background: var(--surface-2); color: var(--muted); border: 1px solid var(--line);
}
.rank.first { background: var(--gold-bg); color: var(--gold-text); border-color: var(--gold-line); }
</style>
