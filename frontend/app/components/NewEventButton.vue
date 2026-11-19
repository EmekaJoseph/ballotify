<template>
    <!-- At the free event limit it shows a lock and opens the explanation instead of the form. -->
    <button type="button" class="btn btn-primary" :title="blocked ? 'Free event limit reached' : undefined" @click="startNewEvent">
        <i class="bi" :class="blocked ? 'bi-lock' : 'bi-plus-lg'"></i> {{ label }}
    </button>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ label?: string }>(), { label: 'New event' });
const { blocked, startNewEvent } = useNewEvent();
</script>
