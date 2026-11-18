<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="open" class="modal-backdrop-b" @mousedown.self="!persistent && emit('close')">
                <div class="modal-panel" :class="size" role="dialog" aria-modal="true" :aria-label="title">
                    <div class="d-flex align-items-start justify-content-between gap-3 px-4 pt-4 pb-2">
                        <div class="min-w-0">
                            <h5 class="mb-1 fw-800">{{ title }}</h5>
                            <p v-if="subtitle" class="text-muted small mb-0">{{ subtitle }}</p>
                        </div>
                        <button type="button" class="btn btn-ghost btn-icon btn-sm flex-shrink-0" aria-label="Close"
                            @click="emit('close')">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="px-4 py-3 modal-body-b">
                        <slot />
                    </div>
                    <div v-if="$slots.footer" class="px-4 pb-4 pt-2 d-flex flex-wrap justify-content-end gap-2">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
    open: boolean;
    title: string;
    subtitle?: string;
    size?: 'sm' | 'md' | 'lg';
    persistent?: boolean;
}>(), { size: 'md', persistent: false, subtitle: '' });

const emit = defineEmits<{ close: [] }>();

function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape' && props.open && !props.persistent) emit('close');
}

watch(() => props.open, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey);
    document.body.style.overflow = '';
});
</script>

<style scoped>
.modal-backdrop-b {
    position: fixed; inset: 0; z-index: 1500; background: rgba(10, 18, 34, .5);
    backdrop-filter: blur(4px); display: flex; align-items: flex-end; justify-content: center; padding: 0;
}
.modal-panel {
    width: 100%; background: var(--surface); border-radius: 22px 22px 0 0; box-shadow: var(--shadow-lg);
    max-height: 92vh; display: flex; flex-direction: column;
}
.modal-body-b { overflow-y: auto; }
@media (min-width: 576px) {
    .modal-backdrop-b { align-items: center; padding: 1.5rem; }
    .modal-panel { border-radius: 22px; }
    .modal-panel.sm { max-width: 420px; }
    .modal-panel.md { max-width: 540px; }
    .modal-panel.lg { max-width: 760px; }
}
.modal-enter-active, .modal-leave-active { transition: opacity .2s var(--ease); }
.modal-enter-active .modal-panel, .modal-leave-active .modal-panel { transition: transform .25s var(--ease); }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal-panel, .modal-leave-to .modal-panel { transform: translateY(24px); }
</style>
