<template>
    <div class="donut" :style="{ width: size + 'px', height: size + 'px' }">
        <svg :viewBox="`0 0 ${size} ${size}`" role="img" :aria-label="ariaLabel">
            <circle :cx="c" :cy="c" :r="r" fill="none" :style="{ stroke: 'var(--track)' }" :stroke-width="stroke" />
            <circle v-for="(seg, i) in segments" :key="i" :cx="c" :cy="c" :r="r" fill="none" :stroke="seg.color"
                :stroke-width="stroke" :stroke-dasharray="`${seg.len} ${circ - seg.len}`" :stroke-dashoffset="-seg.offset"
                stroke-linecap="butt" :transform="`rotate(-90 ${c} ${c})`" class="seg" />
        </svg>
        <div class="donut-center">
            <div class="fw-800 fs-4 lh-1 text-ink">{{ formatNumber(total) }}</div>
            <div class="small-caps mt-1">{{ label }}</div>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{ values: number[]; colors: string[]; size?: number; label?: string }>(), {
    size: 180,
    label: 'votes',
});

const stroke = computed(() => Math.max(14, Math.round(props.size * 0.11)));
const c = computed(() => props.size / 2);
const r = computed(() => (props.size - stroke.value) / 2);
const circ = computed(() => 2 * Math.PI * r.value);
const total = computed(() => props.values.reduce((a, b) => a + b, 0));

const segments = computed(() => {
    if (!total.value) return [];
    const gap = props.values.filter(v => v > 0).length > 1 ? 2 : 0;
    let offset = 0;
    return props.values.map((v, i) => {
        const full = (v / total.value) * circ.value;
        const seg = { len: Math.max(0, full - gap), offset, color: props.colors[i % props.colors.length] ?? '#0e7c77' };
        offset += full;
        return seg;
    }).filter(s => s.len > 0);
});

const ariaLabel = computed(() => `${total.value} ${props.label}`);
</script>

<style scoped>
.donut { position: relative; flex-shrink: 0; }
.donut svg { width: 100%; height: 100%; display: block; }
.seg { transition: stroke-dasharray .8s var(--ease), stroke-dashoffset .8s var(--ease); }
.donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
</style>
