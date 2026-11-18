<template>
    <!-- Ballotify mark: a B made of two stacked ballot cards, with a tick cut out of the lower one. -->
    <svg class="brand-mark" :class="`tone-${tone}`" viewBox="6 6 52 52" :width="size" :height="size" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient :id="gid" gradientUnits="userSpaceOnUse" x1="10" y1="6" x2="54" y2="58">
                <stop offset="0" class="s1" />
                <stop offset="1" class="s2" />
            </linearGradient>
            <mask :id="mid" maskUnits="userSpaceOnUse" x="0" y="0" width="64" height="64">
                <rect x="12" y="8" width="29" height="21" rx="9" fill="#fff" />
                <rect x="12" y="8" width="12" height="21" rx="5" fill="#fff" />
                <rect x="12" y="32" width="36" height="24" rx="10" fill="#fff" />
                <rect x="12" y="32" width="12" height="24" rx="5" fill="#fff" />
                <path d="M19.5 18.5 H31" stroke="#000" stroke-width="3.6" stroke-linecap="round" />
                <path d="M22.5 44 L28.5 50 L39.5 38" fill="none" stroke="#000" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
            </mask>
        </defs>
        <rect width="64" height="64" :fill="`url(#${gid})`" :mask="`url(#${mid})`" />
    </svg>
</template>

<script setup lang="ts">
/**
 * tone: 'auto' follows the app theme; 'on-dark' is the brighter version for dark
 * backgrounds; 'on-light' is the standard version whatever the theme (e.g. print).
 */
withDefaults(defineProps<{ size?: number | string; tone?: 'auto' | 'on-dark' | 'on-light' }>(), { size: 34, tone: 'auto' });
const id = useId();
const gid = `bm-g-${id}`;
const mid = `bm-m-${id}`;
</script>

<style scoped>
.brand-mark { flex-shrink: 0; display: block; }
.s1 { stop-color: #14918a; }
.s2 { stop-color: #7c5cff; }
.tone-on-dark .s1 { stop-color: #2dd4bf; }
.tone-on-dark .s2 { stop-color: #a78bfa; }
</style>

<style>
/* Follows the app theme (unscoped so it can read the <html> attribute). */
:root[data-theme="dark"] .brand-mark.tone-auto .s1 { stop-color: #2dd4bf; }
:root[data-theme="dark"] .brand-mark.tone-auto .s2 { stop-color: #a78bfa; }
</style>
