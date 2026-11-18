<template>
    <NuxtLink :to="to" class="app-logo" :class="{ light }" aria-label="Ballotify home">
        <BrandMark :size="32" :tone="light ? 'on-dark' : 'auto'" />
        <span>Ballotify</span>
    </NuxtLink>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ to?: string; light?: boolean }>(), { to: '/', light: false });
</script>

<style scoped>
.app-logo { display: inline-flex; align-items: center; gap: .45rem; font-weight: 800; font-size: 1.2rem; letter-spacing: -.03em; color: var(--ink); }
.app-logo.light { color: #fff; }
</style>
