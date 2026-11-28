<template>
    <!-- Ranked choice: how the count went, round by round. -->
    <div v-if="rounds.length" class="rounds">
        <div v-if="winnerLine" class="won small mb-2">{{ winnerLine }}</div>
        <template v-if="rounds.length > 1">
            <div class="small fw-bold text-ink mb-1">{{ t('res.rounds') }}</div>
            <div class="table-responsive">
                <table class="table-rounds">
                    <thead>
                        <tr><th></th><th v-for="(r, i) in rounds" :key="i" class="num">{{ t('res.round', { n: i + 1 }) }}</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in category.candidates" :key="c.id" :class="{ win: winnerIds.includes(c.id) }">
                            <td class="cand">{{ c.name }}</td>
                            <td v-for="(r, i) in rounds" :key="i" class="num" :class="{ gone: votesIn(r, c.id) === null }">
                                <template v-if="votesIn(r, c.id) !== null">
                                    {{ formatNumber(votesIn(r, c.id)!) }}
                                    <span v-if="r.eliminated.includes(c.id)" class="out">{{ t('res.out') }}</span>
                                </template>
                            </td>
                        </tr>
                        <tr v-if="rounds.some(r => r.exhausted)" class="exhausted">
                            <td class="cand">{{ t('res.exhausted') }}</td>
                            <td v-for="(r, i) in rounds" :key="i" class="num">{{ r.exhausted ? formatNumber(r.exhausted) : '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
        <p class="note small text-muted mb-0 mt-2">{{ t('res.rankedNote') }}</p>
    </div>
</template>

<script setup lang="ts">
import type { RankedRound, ResultCategory } from '~/types';

const props = defineProps<{ category: ResultCategory }>();

const rounds = computed(() => props.category.rounds ?? []);
const winnerIds = computed(() => props.category.winners ?? []);
const votesIn = (r: RankedRound, id: number) => r.counts.find(c => c.id === id)?.votes ?? null;

/** "Won in round 2 with 5 of 9 votes (55.6%)" for a single winner. */
const winnerLine = computed(() => {
    const last = rounds.value[rounds.value.length - 1];
    if (!last || winnerIds.value.length !== 1) return '';
    const votes = votesIn(last, winnerIds.value[0]!) ?? 0;
    const name = props.category.candidates.find(c => c.id === winnerIds.value[0])?.name ?? '';
    return `${name}: ${t('res.wonInRound', { round: rounds.value.length, votes: formatNumber(votes), total: formatNumber(last.continuing), pct: percent(votes, last.continuing) })}`;
});
</script>

<style scoped>
.rounds { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--line); }
.won { color: var(--brand-600); font-weight: 700; }
.won .bi { color: #d97706; }
.table-rounds { width: 100%; border-collapse: collapse; font-size: .85rem; }
.table-rounds th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); font-weight: 700; padding: .35rem .5rem; border-bottom: 1px solid var(--line); white-space: nowrap; }
.table-rounds td { padding: .4rem .5rem; border-bottom: 1px solid var(--line); }
.table-rounds .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.table-rounds .cand { color: var(--ink); font-weight: 600; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.table-rounds tr.win td { background: color-mix(in srgb, var(--brand-600) 8%, transparent); }
.table-rounds tr.win .cand { font-weight: 800; }
.table-rounds td.gone { background-image: repeating-linear-gradient(135deg, transparent 0 6px, color-mix(in srgb, var(--muted) 12%, transparent) 6px 7px); }
.table-rounds .out { display: inline-block; margin-left: .3rem; font-size: .65rem; font-weight: 800; text-transform: uppercase; color: #b42318; background: #fef3f2; border-radius: 5px; padding: 0 .3rem; }
.table-rounds tr.exhausted td { color: var(--muted); font-style: italic; }
.note { line-height: 1.5; }
</style>
