<template>
    <div class="theme-toggle" role="radiogroup" aria-label="Colour theme">
        <button v-for="o in options" :key="o.value" type="button" role="radio" :aria-checked="pref === o.value"
            :class="{ active: pref === o.value }" :title="o.label" :aria-label="o.label" @click="set(o.value)">
            <i class="bi" :class="o.icon"></i>
        </button>
    </div>
</template>

<script setup lang="ts">
import type { ThemePref } from '~/composables/useTheme';

const { pref, set } = useTheme();
const options: { value: ThemePref; label: string; icon: string }[] = [
    { value: 'light', label: 'Light theme', icon: 'bi-sun' },
    { value: 'system', label: 'Match my device', icon: 'bi-circle-half' },
    { value: 'dark', label: 'Dark theme', icon: 'bi-moon-stars' },
];
</script>

<style scoped>
.theme-toggle { display: inline-flex; gap: 2px; padding: 3px; border-radius: 12px; background: var(--surface-2); border: 1px solid var(--line); }
.theme-toggle button {
    width: 30px; height: 28px; border: 0; border-radius: 9px; background: transparent; color: var(--muted);
    display: inline-flex; align-items: center; justify-content: center; font-size: .9rem;
}
.theme-toggle button:hover { color: var(--ink); }
.theme-toggle button.active { background: var(--surface); color: var(--brand-700); box-shadow: var(--shadow-sm); }
</style>
