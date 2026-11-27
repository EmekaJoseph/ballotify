<template>
    <NuxtLink to="/dashboard/events/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> {{ label }}
    </NuxtLink>
</template>

<script setup lang="ts">
// Drafts are always allowed; the free plan limits live events (see useNewEvent).
withDefaults(defineProps<{ label?: string }>(), { label: 'New event' });
</script>
