<template>
    <!-- Agudatechy mark (the "A" from agudatechy.com), in the brand teal. -->
    <svg class="brand-mark" :class="`tone-${tone}`" viewBox="0 0 334 334" :width="size" :height="size" aria-hidden="true" focusable="false">
        <g transform="translate(0,334) scale(0.1,-0.1)" fill="currentColor" stroke="none">
            <path d="M1647 3334 c-1 -1 -45 -4 -97 -8 -52 -3 -108 -8 -125 -11 -155 -27 -245 -52 -366 -100 -95 -38 -231 -107 -260 -131 -8 -8 -19 -14 -23 -14 -18 0 -197 -144 -270 -217 -267 -264 -434 -599 -484 -968 -21 -148 -10 -408 23 -570 37 -185 149 -439 263 -600 223 -314 579 -564 927 -651 17 -4 32 -8 35 -9 104 -26 118 -28 235 -40 115 -12 355 -7 427 10 13 2 34 7 48 9 179 29 446 139 617 253 298 200 532 504 646 843 39 114 40 116 69 270 19 101 27 345 15 440 -28 216 -74 373 -161 555 -53 109 -151 263 -217 340 -295 345 -695 553 -1134 590 -72 6 -165 11 -168 9z" />
        </g>
    </svg>
</template>

<script setup lang="ts">
/**
 * tone: 'auto' follows the app theme; 'on-dark' is the brighter teal for dark
 * backgrounds; 'on-light' is the deep teal whatever the theme (e.g. print).
 */
withDefaults(defineProps<{ size?: number | string; tone?: 'auto' | 'on-dark' | 'on-light' }>(), { size: 34, tone: 'auto' });
</script>

<style scoped>
.brand-mark { display: inline-block; flex-shrink: 0; vertical-align: middle; color: #058580; }
.tone-on-dark { color: #00d4c9; }
.tone-on-light { color: #058580; }
:global(:root[data-theme="dark"]) .tone-auto { color: #00d4c9; }
</style>
