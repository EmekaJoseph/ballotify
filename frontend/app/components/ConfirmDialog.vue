<template>
    <!-- Confirmations use the same pop-up alert style as errors -->
    <AlertDialog :open="state.open" :title="state.title" :message="state.message || 'Are you sure?'"
        :tone="state.danger ? 'danger' : 'info'"
        :icon-class="state.danger ? 'bi-exclamation-triangle-fill' : 'bi-question-circle-fill'"
        :action-text="state.confirmText || 'Confirm'" :close-text="state.cancelText || 'Cancel'"
        :action-variant="state.danger ? 'danger' : 'primary'"
        @action="settle(true)" @close="settle(false)" />
</template>

<script setup lang="ts">
const { state, settle } = useConfirm();
</script>
