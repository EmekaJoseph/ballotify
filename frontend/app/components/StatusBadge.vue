<template>
    <span class="pill" :class="meta.cls"><span class="dot"></span>{{ meta.label }}</span>
</template>

<script setup lang="ts">
import type { EventStatus } from '~/types';

const props = defineProps<{ status: EventStatus | undefined }>();
const meta = computed(() => STATUS_META[props.status ?? 'upcoming']);
</script>
