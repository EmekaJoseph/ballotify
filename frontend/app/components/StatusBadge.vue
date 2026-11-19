<template>
    <span v-if="draft" class="pill pill-completed" title="No voters yet. It goes live when you add voters."><i class="bi bi-pencil-square"></i> Draft</span>
    <span v-else class="pill" :class="meta.cls"><span class="dot"></span>{{ meta.label }}</span>
</template>

<script setup lang="ts">
import type { EventStatus } from '~/types';

/** draft: a code-voting event without voters yet (not "live" for the free-plan limit). */
const props = defineProps<{ status: EventStatus | undefined; draft?: boolean }>();
const meta = computed(() => STATUS_META[props.status ?? 'upcoming']);
</script>
