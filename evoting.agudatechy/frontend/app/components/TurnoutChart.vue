<template>
    <div ref="wrap" class="turnout" :style="{ height: height + 'px' }">
        <div v-if="loading" class="skeleton h-100"></div>
        <div v-else-if="!points.length" class="empty small text-muted"><i class="bi bi-graph-up"></i> {{ t('chart.empty') }}</div>
        <template v-else>
            <svg :width="w" :height="height" role="img" :aria-label="summary" @pointermove="onMove" @pointerleave="hover = null">
                <!-- recessive grid + y labels -->
                <g v-for="t in yTicks" :key="t">
                    <line :x1="PAD_L" :x2="w - PAD_R" :y1="y(t)" :y2="y(t)" class="grid" />
                    <text :x="PAD_L - 6" :y="y(t)" class="axis" text-anchor="end" dominant-baseline="middle">{{ compact(t) }}</text>
                </g>
                <text v-for="(p, i) in xLabels" :key="i" :x="x(p.i)" :y="height - 6" class="axis" :text-anchor="p.anchor">{{ p.label }}</text>

                <path :d="area" class="area" />
                <path :d="line" class="line" />

                <!-- hover -->
                <g v-if="hover !== null">
                    <line :x1="x(hover)" :x2="x(hover)" :y1="PAD_T" :y2="height - PAD_B" class="cross" />
                    <circle :cx="x(hover)" :cy="y(cumulative[hover]!)" r="5" class="dot" />
                </g>
            </svg>
            <div v-if="hover !== null" class="tip" :style="tipStyle">
                <div class="fw-800 text-ink">{{ formatNumber(cumulative[hover]!) }} {{ unit }}</div>
                <div class="small text-muted">+{{ formatNumber(points[hover]!.votes) }} · {{ bucketLabel(points[hover]!.t) }}</div>
            </div>
            <!-- Same data as a table, for screen readers -->
            <table class="visually-hidden">
                <caption>{{ unit }} over time</caption>
                <thead><tr><th>Period starting</th><th>New</th><th>Total</th></tr></thead>
                <tbody><tr v-for="(p, i) in points" :key="p.t"><td>{{ bucketLabel(p.t) }}</td><td>{{ p.votes }}</td><td>{{ cumulative[i] }}</td></tr></tbody>
            </table>
        </template>
    </div>
</template>

<script setup lang="ts">
/** Cumulative votes (or ballots) over time: one series, so no legend; the card title names it. */
const props = withDefaults(defineProps<{
    timeline?: { bucket_minutes: number; points: { t: string; votes: number }[] } | null;
    height?: number;
    unit?: string;
    loading?: boolean;
}>(), { timeline: null, height: 180, unit: 'ballots', loading: false });

const PAD_L = 34, PAD_R = 10, PAD_T = 10, PAD_B = 22;
const wrap = ref<HTMLElement | null>(null);
const w = ref(600);
let ro: ResizeObserver | null = null;
onMounted(() => {
    ro = new ResizeObserver(([e]) => { w.value = Math.max(240, Math.floor(e!.contentRect.width)); });
    if (wrap.value) ro.observe(wrap.value);
});
onBeforeUnmount(() => ro?.disconnect());

const points = computed(() => props.timeline?.points ?? []);
const cumulative = computed(() => {
    let sum = 0;
    return points.value.map(p => (sum += p.votes));
});
const max = computed(() => Math.max(1, ...cumulative.value));
/** A "nice" top for the axis. */
const top = computed(() => {
    const m = max.value;
    const step = Math.pow(10, Math.floor(Math.log10(m)));
    return Math.ceil(m / step) * step;
});
const yTicks = computed(() => [0, top.value / 2, top.value]);

const n = computed(() => Math.max(1, points.value.length - 1));
const x = (i: number) => PAD_L + (i / n.value) * (w.value - PAD_L - PAD_R);
const y = (v: number) => PAD_T + (1 - v / top.value) * (props.height - PAD_T - PAD_B);

const line = computed(() => cumulative.value.map((v, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(v).toFixed(1)}`).join(''));
const area = computed(() => points.value.length
    ? `${line.value}L${x(points.value.length - 1).toFixed(1)},${y(0)}L${x(0).toFixed(1)},${y(0)}Z` : '');

const minutes = computed(() => props.timeline?.bucket_minutes ?? 60);
function bucketLabel(iso: string) {
    const d = new Date(iso);
    return minutes.value >= 1440
        ? d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' })
        : d.toLocaleString(undefined, { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });
}
/** Axis label: time only within one day; date (plus hour for multi-hour buckets) across days. */
const spanHours = computed(() => (points.value.length * minutes.value) / 60);
function shortLabel(iso: string) {
    const d = new Date(iso);
    if (minutes.value >= 1440) return d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
    if (spanHours.value > 20) return d.toLocaleString(undefined, { day: 'numeric', month: 'short', hour: 'numeric' });
    return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}
const xLabels = computed(() => {
    const last = points.value.length - 1;
    if (last < 0) return [];
    const out = [{ i: 0, label: shortLabel(points.value[0]!.t), anchor: 'start' }];
    if (last >= 2 && w.value > 360) out.push({ i: Math.round(last / 2), label: shortLabel(points.value[Math.round(last / 2)]!.t), anchor: 'middle' });
    if (last >= 1) out.push({ i: last, label: shortLabel(points.value[last]!.t), anchor: 'end' });
    return out;
});
const compact = (v: number) => (v >= 1000 ? `${Math.round(v / 100) / 10}k` : String(Math.round(v)));

// ----- hover: snap to the nearest bucket; the hit area is the whole plot
const hover = ref<number | null>(null);
function onMove(e: PointerEvent) {
    const rect = (e.currentTarget as SVGElement).getBoundingClientRect();
    const px = e.clientX - rect.left;
    const i = Math.round(((px - PAD_L) / (w.value - PAD_L - PAD_R)) * n.value);
    hover.value = Math.min(points.value.length - 1, Math.max(0, i));
}
const tipStyle = computed(() => {
    if (hover.value === null) return {};
    const left = x(hover.value);
    return { left: `${Math.min(Math.max(left, 70), w.value - 70)}px`, top: `${Math.max(0, y(cumulative.value[hover.value]!) - 58)}px` };
});

const summary = computed(() => `${props.unit} over time: ${formatNumber(cumulative.value.at(-1) ?? 0)} in total`);
</script>

<style scoped>
.turnout { position: relative; width: 100%; }
.empty { height: 100%; display: grid; place-items: center; border: 1px dashed var(--line); border-radius: 12px; }
svg { display: block; overflow: visible; touch-action: pan-y; }
.grid { stroke: var(--line); stroke-width: 1; }
.axis { fill: var(--muted); font-size: 11px; }
.line { fill: none; stroke: var(--brand-600); stroke-width: 2; stroke-linejoin: round; stroke-linecap: round; }
.area { fill: var(--brand-600); opacity: .12; }
.cross { stroke: var(--line-strong); stroke-width: 1; stroke-dasharray: 3 3; }
.dot { fill: var(--brand-600); stroke: var(--surface); stroke-width: 2; }
.tip {
    position: absolute; transform: translateX(-50%); pointer-events: none; white-space: nowrap;
    background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: .35rem .6rem; box-shadow: var(--shadow-lg);
}
</style>
