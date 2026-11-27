<template>
    <img v-if="src && !failed" :src="src" :alt="name" class="avatar" :class="size" loading="lazy" @error="failed = true" />
    <span v-else class="avatar" :class="size" :style="{ background: colorFor(name) }" aria-hidden="true">{{ initials(name) }}</span>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{ name: string; src?: string | null; size?: '' | 'sm' | 'lg' | 'xl' }>(), {
    src: null,
    size: '',
});
const failed = ref(false);
watch(() => props.src, () => { failed.value = false; });
</script>
