<template>
    <div class="stat-wrap h-100">
        <div class="card-b stat h-100">
            <div v-if="adminIcons" class="icon-tile" :class="tone"><i class="bi" :class="icon"></i></div>
            <div class="stat-body">
                <div class="small-caps stat-label">{{ label }}</div>
                <div class="stat-value fw-800 lh-1 text-ink">{{ value }}</div>
                <div v-if="hint" class="small text-muted mt-1 stat-hint">{{ hint }}</div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">

const adminIcons = useAdminIcons();
withDefaults(defineProps<{ label: string; value: string | number; icon: string; tone?: '' | 'violet' | 'green' | 'amber'; hint?: string }>(), {
    tone: '',
    hint: '',
});
</script>

<style scoped>
/*
 * The layout follows the card's OWN width (container query), not the window:
 * the same card sits in wide 4-up rows, narrow sidebars and 2-up phone grids.
 */
.stat-wrap { container-type: inline-size; min-width: 0; }
.stat { display: flex; flex-direction: column; align-items: flex-start; gap: .7rem; padding: 1rem; min-width: 0; }
.stat .icon-tile { width: 36px; height: 36px; font-size: 1rem; border-radius: 10px; }
.stat-body { min-width: 0; width: 100%; }
.stat-label { margin-bottom: .3rem; overflow-wrap: break-word; }
.stat-value { font-size: 1.45rem; overflow-wrap: break-word; }
.stat-hint { overflow-wrap: break-word; }

@container (min-width: 230px) {
    .stat { flex-direction: row; align-items: center; gap: 1rem; padding: 1.25rem 1.4rem; }
    .stat .icon-tile { width: 44px; height: 44px; font-size: 1.2rem; border-radius: 12px; }
    .stat-value { font-size: 1.7rem; }
}
</style>
