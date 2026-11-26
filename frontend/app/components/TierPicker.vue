<template>
    <!-- Choose a Pro size. Sizes at or below the current one, or too small for the voters already added, can't be chosen. -->
    <div class="tiers" role="radiogroup" aria-label="Pro size">
        <label v-for="t in tiers" :key="t.key" class="tier" :class="{ on: model === t.key, off: !choosable(t), current: current === t.key }"
            :title="reason(t)">
            <input v-model="model" type="radio" class="visually-hidden" :value="t.key" :disabled="!choosable(t)" />
            <span class="d-flex justify-content-between align-items-start gap-2">
                <span class="name">{{ t.name }}</span>
                <span v-if="current === t.key" class="pill pill-pro"><i class="bi bi-check2"></i> Current</span>
                <span v-else-if="t.key === 'standard' && !current" class="pill pill-brand">Popular</span>
            </span>
            <span class="price">
                <template v-if="current && choosable(t)">+{{ formatNaira(price(t)) }}</template>
                <template v-else>{{ formatNaira(t.price_naira) }}</template>
            </span>
            <span class="meta"><i class="bi bi-people"></i> Up to {{ formatNumber(t.max_voters) }} voters</span>
            <span class="meta"><i class="bi bi-chat-dots"></i> {{ formatNumber(t.sms_included) }} SMS included</span>
        </label>
    </div>
</template>

<script setup lang="ts">
import type { PlanSize, PlanTier, ProTier } from '~/types';

const props = withDefaults(defineProps<{ tiers: PlanTier[]; current?: PlanSize | null; voters?: number }>(), { current: null, voters: 0 });
const model = defineModel<ProTier | null>({ default: null });

// Pro Unlimited (admin only) isn't for sale: it ranks above every size, so nothing is choosable.
const order = (k: string) => (k === 'unlimited' ? Number.POSITIVE_INFINITY : props.tiers.findIndex(t => t.key === k));
const currentPrice = computed(() => props.tiers.find(t => t.key === props.current)?.price_naira ?? 0);
/** Upgrading pays the difference from the current size. */
const price = (t: PlanTier) => Math.max(0, t.price_naira - currentPrice.value);
const choosable = (t: PlanTier) => (!props.current || order(t.key) > order(props.current)) && t.max_voters >= props.voters;
const reason = (t: PlanTier) => {
    if (props.current && order(t.key) <= order(props.current)) return props.current === t.key ? 'This event’s current size' : 'Smaller than the current size';
    if (t.max_voters < props.voters) return `This event already has ${formatNumber(props.voters)} voters`;
    return undefined;
};

// Pre-select a sensible size: the popular one if possible, else the smallest that fits.
watchEffect(() => {
    if (model.value && props.tiers.some(t => t.key === model.value && choosable(t))) return;
    model.value = (props.tiers.find(t => t.key === 'standard' && choosable(t)) ?? props.tiers.find(choosable))?.key ?? null;
});
</script>

<style scoped>
.tiers { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .6rem; }
@media (max-width: 575.98px) { .tiers { grid-template-columns: 1fr; } }
.tier {
    display: flex; flex-direction: column; gap: .3rem; padding: .85rem .9rem; border-radius: 14px; cursor: pointer;
    border: 1.5px solid var(--line); background: var(--surface); transition: border-color .15s, background-color .15s, box-shadow .15s;
}
.tier:hover:not(.off) { border-color: var(--brand-500); }
.tier.on { border-color: var(--brand-500); background: var(--brand-50); box-shadow: 0 0 0 3px rgba(20, 145, 138, .12); }
.tier.off { cursor: not-allowed; opacity: .55; }
.tier.current { opacity: 1; border-style: dashed; }
.name { font-weight: 800; color: var(--ink); }
.price { font-size: 1.35rem; font-weight: 800; color: var(--ink); letter-spacing: -.01em; }
.meta { font-size: .8rem; color: var(--muted); display: flex; gap: .35rem; align-items: center; }
.tier .pill { font-size: .65rem; padding: .15rem .45rem; }
</style>
