<template>
    <!-- The Agudatechy "A" mark: the top part of /brand/logo.png, cropped by the viewBox (no text). -->
    <svg class="brand-mark" viewBox="88 -34 480 480" :width="size" :height="size" aria-hidden="true" focusable="false">
        <image href="/brand/logo.png" x="0" y="0" width="635" height="596" />
    </svg>
</template>

<script setup lang="ts">
/** tone is kept so existing callers still work; the mark is the same teal on light and dark. */
withDefaults(defineProps<{ size?: number | string; tone?: 'auto' | 'on-dark' | 'on-light' }>(), { size: 34, tone: 'auto' });
</script>

<style scoped>
.brand-mark { display: inline-block; flex-shrink: 0; vertical-align: middle; overflow: hidden; }
</style>
