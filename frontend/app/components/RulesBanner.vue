<template>
    <div v-if="rules" class="rules-banner" :class="tone">
        <div v-if="q" class="q-row">
            <i class="bi" :class="q.met ? 'bi-check-circle-fill' : (closed ? 'bi-x-octagon-fill' : 'bi-hourglass-split')"></i>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-bold">{{ quorumText }}</div>
                <div v-if="!closed || !q.met" class="q-bar" role="progressbar" :aria-valuenow="q.achieved" :aria-valuemin="0" :aria-valuemax="q.required">
                    <span :style="{ width: Math.min(100, percent(q.achieved, q.required)) + '%' }"></span>
                </div>
            </div>
        </div>
        <ul v-if="notes.length" class="notes" :class="{ 'mt-2': q }">
            <li v-for="n in notes" :key="n"><i class="bi bi-dot"></i>{{ n }}</li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import type { MeetingRulesSummary } from '~/types';

/**
 * Weighted votes / proxies / quorum in force for an event, with live quorum
 * progress. Shown on the public results page and the organiser's results tab.
 */
const props = defineProps<{ rules: MeetingRulesSummary | null | undefined; status: string }>();

const q = computed(() => props.rules?.quorum ?? null);
const closed = computed(() => props.status === 'completed');
const tone = computed(() => (!q.value ? 'neutral' : q.value.met ? 'ok' : closed.value ? 'bad' : 'wait'));

const quorumText = computed(() => {
    const quorum = q.value!;
    const params = {
        achieved: formatNumber(quorum.achieved),
        required: formatNumber(quorum.required),
        unit: quorum.basis === 'weight' ? t('rules.unitVotes') : t('rules.unitMembers'),
    };
    if (quorum.met) return t('rules.quorumMet', params);
    return closed.value ? t('rules.quorumFailed', params) : t('rules.quorumProgress', params);
});

const notes = computed(() => {
    const r = props.rules;
    if (!r) return [];
    const out: string[] = [];
    if (r.weighted) out.push(t('rules.weighted'));
    if (r.proxies) out.push(r.proxy_votes ? tn('rules.proxies', r.proxy_votes) : t('rules.proxiesOn'));
    return out;
});
</script>

<style scoped>
.rules-banner { padding: .8rem 1rem; border-radius: var(--radius); font-size: .9rem; background: var(--surface-2); color: var(--ink-2); box-shadow: inset 0 0 0 1px var(--line); overflow-wrap: anywhere; }
.rules-banner.ok { background: var(--success-50); color: var(--success); box-shadow: none; }
.rules-banner.wait { background: var(--warning-50); color: var(--warning-text); box-shadow: none; }
.rules-banner.bad { background: var(--danger-50, #fdecec); color: var(--danger, #c0362c); box-shadow: inset 0 0 0 1px currentColor; }
.q-row { display: flex; gap: .6rem; align-items: flex-start; }
.q-row > .bi { font-size: 1.1rem; line-height: 1.3; }
.q-bar { height: 6px; border-radius: 99px; background: color-mix(in srgb, currentColor 18%, transparent); margin-top: .45rem; overflow: hidden; }
.q-bar span { display: block; height: 100%; border-radius: 99px; background: currentColor; transition: width .4s ease; }
.notes { list-style: none; padding: 0; margin: 0; display: grid; gap: .15rem; font-size: .85rem; }
.notes .bi { margin-left: -.3rem; }
.rules-banner.ok .notes, .rules-banner.wait .notes, .rules-banner.bad .notes { color: var(--ink-2); }
</style>
