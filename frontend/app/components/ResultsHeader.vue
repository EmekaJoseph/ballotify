<template>
    <div class="results-hero mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-end">
            <div class="min-w-0">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                    <span v-if="status === 'active'" class="pill pill-live live"><span class="dot"></span>Live results</span>
                    <span v-else-if="status === 'completed'" class="pill final"><i class="bi bi-flag-fill"></i> Final results</span>
                    <span v-else class="pill soon"><i class="bi bi-clock"></i> Voting not started</span>
                    <span v-if="updatedAt" class="small hero-muted">Updated {{ updatedAt }}</span>
                </div>
                <h1 class="h2 fw-800 text-white mb-1 text-break">{{ name }}</h1>
                <div v-if="status === 'active' && closesIn.label" class="hero-muted small">
                    <i class="bi bi-hourglass-split"></i> Voting closes in <b class="text-white">{{ closesIn.label }}</b>
                </div>
                <div v-else-if="status === 'completed' && endsAt" class="hero-muted small">Closed {{ formatDate(endsAt, true) }}</div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0 no-print">
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { EventStatus } from '~/types';

const props = defineProps<{ name: string; status: EventStatus; endsAt: string | null; updatedAt?: string }>();
const closesIn = useCountdown(() => props.endsAt);
</script>

<style scoped>
.results-hero {
    border-radius: var(--radius-xl); padding: 1.75rem 1.5rem; color: #fff;
    background:
        radial-gradient(520px 260px at 100% 0%, rgba(124, 92, 255, .45), transparent 60%),
        radial-gradient(520px 260px at 0% 100%, rgba(20, 145, 138, .5), transparent 60%),
        var(--night);
}
@media (min-width: 768px) { .results-hero { padding: 2.25rem 2.25rem; } }
.hero-muted { color: rgba(255, 255, 255, .65); }
.live { background: rgba(74, 222, 128, .15); color: #86efac; }
.final { background: rgba(255, 255, 255, .12); color: #fff; }
.soon { background: rgba(251, 191, 36, .15); color: #fcd34d; }
</style>
