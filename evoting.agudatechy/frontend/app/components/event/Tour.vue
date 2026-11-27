<template>
    <Teleport to="body">
        <div v-if="current" class="tour-layer" role="dialog" aria-modal="false" :aria-label="`Tour step ${index + 1} of ${steps.length}`">
            <!-- Spotlight around the element being explained -->
            <div v-if="rect" class="spot" :style="spotStyle"></div>
            <div class="tip card-b" :style="tipStyle">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small-caps">Step {{ index + 1 }} of {{ steps.length }}</span>
                    <button type="button" class="btn btn-link btn-sm text-muted p-0" @click="finish">Skip tour</button>
                </div>
                <h3 class="h6 fw-800 mb-1">{{ current.title }}</h3>
                <p class="small text-muted mb-3">{{ current.text }}</p>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="dots" aria-hidden="true"><span v-for="(s, i) in steps" :key="i" :class="{ on: i === index }"></span></div>
                    <div class="d-flex gap-2">
                        <button v-if="index > 0" type="button" class="btn btn-ghost btn-sm" @click="go(index - 1)">Back</button>
                        <button type="button" class="btn btn-primary btn-sm" @click="index === steps.length - 1 ? finish() : go(index + 1)">
                            {{ index === steps.length - 1 ? 'Got it' : 'Next' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
/** A short first-run tour of the event page. Steps whose target isn't on screen are skipped. */
const emit = defineEmits<{ done: [] }>();

const ALL = [
    { target: '[data-tour="tab-ballot"]', title: '1. Build the ballot', text: 'Add categories (like “President”) and the candidates in each, with photos and short descriptions.' },
    { target: '[data-tour="tab-voters"]', title: '2. Add voters', text: 'Import your voter list or generate codes. Send them by email, SMS or WhatsApp, or print slips.' },
    { target: '[data-tour="tab-share"]', title: '3. Share and watch', text: 'Share the voting link, then follow turnout live. Present the results on a projector when it’s time.' },
    { target: '[data-tour="checklist"]', title: 'Your checklist', text: 'It ticks itself off as you go. Click any step to jump straight to it.' },
];

const steps = ref(ALL);
const index = ref(0);
const rect = ref<DOMRect | null>(null);
const current = computed(() => steps.value[index.value] ?? null);

function measure() {
    const el = current.value ? document.querySelector(current.value.target) : null;
    rect.value = el ? el.getBoundingClientRect() : null;
}
function go(i: number) {
    index.value = i;
    const el = current.value ? document.querySelector(current.value.target) : null;
    el?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    nextTick(measure);
    setTimeout(measure, 350);
}
function finish() {
    steps.value = [];
    emit('done');
}

const spotStyle = computed(() => {
    const r = rect.value!;
    return { top: `${r.top - 6}px`, left: `${r.left - 6}px`, width: `${r.width + 12}px`, height: `${r.height + 12}px` };
});
const tipStyle = computed(() => {
    const r = rect.value;
    const width = Math.min(320, window.innerWidth - 32);
    if (!r) return { top: '50%', left: '50%', width: `${width}px`, transform: 'translate(-50%, -50%)' };
    const below = r.bottom + 14 + 190 < window.innerHeight;
    const left = Math.min(Math.max(16, r.left + r.width / 2 - width / 2), window.innerWidth - width - 16);
    return { width: `${width}px`, left: `${left}px`, top: below ? `${r.bottom + 14}px` : `${Math.max(16, r.top - 14 - 190)}px` };
});

function onKey(e: KeyboardEvent) { if (e.key === 'Escape') finish(); }
onMounted(() => {
    // Only steps that exist on this page (e.g. no Voters tab for paid voting).
    steps.value = ALL.filter(s => document.querySelector(s.target));
    if (!steps.value.length) return finish();
    go(0);
    window.addEventListener('resize', measure);
    window.addEventListener('scroll', measure, true);
    window.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
    window.removeEventListener('resize', measure);
    window.removeEventListener('scroll', measure, true);
    window.removeEventListener('keydown', onKey);
});
</script>

<style scoped>
.tour-layer { position: fixed; inset: 0; z-index: 1400; pointer-events: none; }
.spot {
    position: fixed; border-radius: 14px; pointer-events: none; transition: all .25s var(--ease);
    box-shadow: 0 0 0 9999px rgba(10, 18, 34, .45), 0 0 0 3px var(--brand-500);
}
.tip { position: fixed; padding: 1rem 1.1rem; pointer-events: auto; box-shadow: var(--shadow-lg); transition: top .25s var(--ease), left .25s var(--ease); }
.dots { display: flex; gap: 4px; }
.dots span { width: 6px; height: 6px; border-radius: 50%; background: var(--line-strong); }
.dots span.on { background: var(--brand-600); width: 16px; border-radius: 4px; }
</style>
