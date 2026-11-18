<template>
    <div class="card-b text-center px-4 py-5 fade-up">
        <div class="check-wrap mx-auto mb-4" aria-hidden="true">
            <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="24" /><path d="M15 27l7 7 15-15" /></svg>
        </div>
        <h1 class="h3 fw-800 mb-2">Your vote has been recorded</h1>
        <p class="text-muted mx-auto mb-4" style="max-width: 420px">
            Thank you for voting in <b class="text-ink">{{ eventName }}</b>. Your code has now been used and can't vote again.
        </p>
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <NuxtLink v-if="resultsTo" :to="resultsTo" class="btn btn-primary"><i class="bi bi-bar-chart-line"></i> View live results</NuxtLink>
            <NuxtLink to="/" class="btn btn-ghost">Done</NuxtLink>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{ eventName: string; resultsTo?: string }>();
</script>

<style scoped>
.check-wrap { width: 92px; height: 92px; border-radius: 50%; background: var(--success-50); padding: 14px; }
.check-wrap svg { width: 100%; height: 100%; }
.check-wrap circle { fill: none; stroke: var(--success); stroke-width: 3; stroke-dasharray: 151; stroke-dashoffset: 151; animation: draw .6s var(--ease) forwards; }
.check-wrap path { fill: none; stroke: var(--success); stroke-width: 4; stroke-linecap: round; stroke-linejoin: round; stroke-dasharray: 36; stroke-dashoffset: 36; animation: draw .4s .45s var(--ease) forwards; }
@keyframes draw { to { stroke-dashoffset: 0; } }
</style>
