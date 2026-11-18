<template>
    <BaseModal :open="state.open" :title="state.title" size="sm" @close="settle(false)">
        <div class="d-flex gap-3 align-items-start">
            <div class="icon-tile" :class="state.danger ? 'danger' : ''">
                <i class="bi" :class="state.danger ? 'bi-exclamation-triangle' : 'bi-question-circle'"></i>
            </div>
            <p class="text-muted mb-0 pt-1">{{ state.message || 'Are you sure?' }}</p>
        </div>
        <template #footer>
            <button type="button" class="btn btn-ghost" @click="settle(false)">{{ state.cancelText || 'Cancel' }}</button>
            <button type="button" class="btn" :class="state.danger ? 'btn-danger' : 'btn-primary'" @click="settle(true)">
                {{ state.confirmText || 'Confirm' }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
const { state, settle } = useConfirm();
</script>

<style scoped>
.icon-tile.danger { background: var(--danger-50); color: var(--danger); }
</style>
