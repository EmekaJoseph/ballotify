<template>
    <NuxtLink :to="to" class="app-logo" :class="{ light }" aria-label="E-voting by Agudatechy home">
        <BrandMark :size="32" :tone="light ? 'on-dark' : 'auto'" />
        <span class="name">E-voting<small>by Agudatechy</small></span>
    </NuxtLink>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ to?: string; light?: boolean }>(), { to: '/', light: false });
</script>

<style scoped>
.app-logo { display: inline-flex; align-items: center; gap: .45rem; font-weight: 800; font-size: 1.2rem; letter-spacing: -.03em; color: var(--ink); }
.app-logo.light { color: #fff; }
.name { display: inline-flex; flex-direction: column; line-height: 1.05; }
.name small { font-size: .62rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
.app-logo.light .name small { color: rgba(255, 255, 255, .6); }
</style>
