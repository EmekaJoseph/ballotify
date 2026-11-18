<template>
    <div class="demo-banner mb-4">
        <i class="bi bi-eye"></i>
        <span class="flex-grow-1">{{ text }}</span>
        <NuxtLink to="/signup" class="fw-bold text-nowrap">Create yours <i class="bi bi-arrow-right"></i></NuxtLink>
    </div>
</template>

<script setup lang="ts">
defineProps<{ text: string }>();
</script>

<style scoped>
.demo-banner {
    display: flex; align-items: center; gap: .65rem; flex-wrap: wrap; padding: .75rem 1rem; border-radius: 14px;
    background: var(--accent-50); color: #5b3fd1; font-size: .9rem; font-weight: 500;
}
.demo-banner a { color: #5b3fd1; }
</style>
