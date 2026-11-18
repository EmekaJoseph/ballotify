<template>
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
        <div class="min-w-0">
            <NuxtLink v-if="back" :to="back" class="small fw-600 text-muted d-inline-flex align-items-center gap-1 mb-2 back-link">
                <i class="bi bi-arrow-left"></i> {{ backLabel }}
            </NuxtLink>
            <h1 class="h3 fw-800 mb-1 text-break">{{ title }}</h1>
            <p v-if="subtitle" class="text-muted mb-0">{{ subtitle }}</p>
            <slot name="meta" />
        </div>
        <div v-if="$slots.default" class="d-flex flex-wrap gap-2 flex-shrink-0">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ title: string; subtitle?: string; back?: string; backLabel?: string }>(), {
    subtitle: '',
    back: '',
    backLabel: 'Back',
});
</script>

<style scoped>
.back-link:hover { color: var(--brand-600) !important; }
.fw-600 { font-weight: 600; }
</style>
