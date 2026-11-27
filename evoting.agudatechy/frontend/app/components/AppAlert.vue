<template>
    <AlertDialog :open="state.open" :title="state.title || defaultTitle" :message="state.message" :tone="state.tone"
        :action-text="state.actionText" :close-text="state.closeText || (state.actionText ? 'Close' : 'OK')"
        @action="settle(true)" @close="settle(false)" />
</template>

<script setup lang="ts">
const { state, settle } = useAlert();
const defaultTitle = computed(() => ({
    danger: 'Something went wrong',
    warning: 'Please check',
    info: 'Good to know',
    success: 'Done',
})[state.value.tone ?? 'danger']);
</script>
