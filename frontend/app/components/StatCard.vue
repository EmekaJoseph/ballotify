<template>
    <div class="card-b stat h-100">
        <div class="icon-tile" :class="tone"><i class="bi" :class="icon"></i></div>
        <div class="min-w-0">
            <div class="small-caps mb-1">{{ label }}</div>
            <div class="stat-value fw-800 lh-1 text-ink">{{ value }}</div>
            <div v-if="hint" class="small text-muted mt-1">{{ hint }}</div>
        </div>
    </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ label: string; value: string | number; icon: string; tone?: '' | 'violet' | 'green' | 'amber'; hint?: string }>(), {
    tone: '',
    hint: '',
});
</script>

<style scoped>
/* Stacked on phones (2-up grids are narrow), side by side from 576px. */
.stat { display: flex; flex-direction: column; gap: .75rem; padding: 1rem; }
.stat .icon-tile { width: 38px; height: 38px; font-size: 1.05rem; }
.stat-value { font-size: 1.5rem; }
@media (min-width: 576px) {
    .stat { flex-direction: row; align-items: center; gap: 1rem; padding: 1.5rem; }
    .stat .icon-tile { width: 44px; height: 44px; font-size: 1.2rem; }
    .stat-value { font-size: 1.75rem; }
}
</style>
