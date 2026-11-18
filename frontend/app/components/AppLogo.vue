<template>
    <NuxtLink :to="to" class="app-logo" :class="{ light }" aria-label="Ballotify home">
        <img src="/ballotify-logo.png" alt="" width="34" height="34" />
        <span>Ballotify</span>
    </NuxtLink>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ to?: string; light?: boolean }>(), { to: '/', light: false });
</script>

<style scoped>
.app-logo { display: inline-flex; align-items: center; gap: .6rem; font-weight: 800; font-size: 1.2rem; letter-spacing: -.03em; color: var(--ink); }
.app-logo img { width: 34px; height: 34px; border-radius: 50%; }
.app-logo.light { color: #fff; }
</style>
