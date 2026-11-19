<template>
    <div class="card-b text-center px-4 py-5 fade-up">
        <div class="check-wrap mx-auto mb-4" aria-hidden="true">
            <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="24" /><path d="M15 27l7 7 15-15" /></svg>
        </div>
        <h1 class="h3 fw-800 mb-2">{{ title || (voterName ? `Thank you, ${firstName}!` : 'Your vote has been recorded') }}</h1>
        <p class="text-muted mx-auto mb-4" style="max-width: 440px">
            <template v-if="message">{{ message }}</template>
            <template v-else>
                <template v-if="voterName">Your vote has been recorded</template><template v-else>Thank you for voting</template>
                in <b class="text-ink">{{ eventName }}</b>. Your code has now been used and can't vote again.
            </template>
        </p>

        <!-- Receipt: proof the ballot was counted (it says nothing about the choices) -->
        <div v-if="receipt" class="receipt mx-auto mb-4">
            <div class="small-caps mb-1">{{ receiptLabel }}</div>
            <div class="d-flex align-items-center justify-content-center gap-2">
                <span class="mono fw-800 fs-5 text-ink">{{ receipt }}</span>
                <button type="button" class="btn btn-ghost btn-icon btn-sm" :title="copied ? 'Copied' : 'Copy'" :aria-label="copied ? 'Copied' : 'Copy receipt'" @click="copy">
                    <i class="bi" :class="copied ? 'bi-check2' : 'bi-copy'"></i>
                </button>
            </div>
            <div class="small text-muted mt-1">{{ receiptHint }}</div>
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <NuxtLink v-if="resultsTo" :to="resultsTo" class="btn btn-primary"><i class="bi bi-bar-chart-line"></i> {{ resultsLabel }}</NuxtLink>
            <slot name="actions" />
            <NuxtLink v-if="!hideDone" to="/" class="btn btn-ghost">Done</NuxtLink>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
    eventName: string;
    resultsTo?: string;
    resultsLabel?: string;
    voterName?: string | null;
    title?: string;
    message?: string;
    receipt?: string | null;
    receiptLabel?: string;
    receiptHint?: string;
    hideDone?: boolean;
}>(), {
    resultsTo: '',
    resultsLabel: 'View live results',
    voterName: null,
    title: '',
    message: '',
    receipt: null,
    receiptLabel: 'Your ballot receipt',
    receiptHint: 'Save it: you can check on the results page that your ballot was counted. It doesn’t reveal how you voted.',
    hideDone: false,
});
const firstName = computed(() => props.voterName?.trim().split(/\s+/)[0] ?? '');

const copied = ref(false);
async function copy() {
    if (!props.receipt) return;
    try {
        await navigator.clipboard.writeText(props.receipt);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 1800);
    } catch { /* clipboard blocked: the code is on screen */ }
}
</script>

<style scoped>
.check-wrap { width: 92px; height: 92px; border-radius: 50%; background: var(--success-50); padding: 14px; }
.check-wrap svg { width: 100%; height: 100%; }
.check-wrap circle { fill: none; stroke: var(--success); stroke-width: 3; stroke-dasharray: 151; stroke-dashoffset: 151; animation: draw .6s var(--ease) forwards; }
.check-wrap path { fill: none; stroke: var(--success); stroke-width: 4; stroke-linecap: round; stroke-linejoin: round; stroke-dasharray: 36; stroke-dashoffset: 36; animation: draw .4s .45s var(--ease) forwards; }
@keyframes draw { to { stroke-dashoffset: 0; } }
.receipt { max-width: 380px; padding: 1rem 1.25rem; border-radius: 16px; border: 1.5px dashed var(--line-strong); background: var(--surface-2); }
</style>
