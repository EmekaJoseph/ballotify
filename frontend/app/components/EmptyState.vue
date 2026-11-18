<template>
    <div class="text-center py-5 px-3">
        <div class="icon-tile lg mx-auto mb-3"><i class="bi" :class="icon"></i></div>
        <h5 class="fw-800 mb-1">{{ title }}</h5>
        <p v-if="text" class="text-muted mx-auto mb-4" style="max-width: 420px">{{ text }}</p>
        <slot />
    </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ icon?: string; title: string; text?: string }>(), { icon: 'bi-inbox', text: '' });
</script>
