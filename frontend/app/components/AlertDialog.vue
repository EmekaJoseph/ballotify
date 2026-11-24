<template>
    <Teleport to="body">
        <Transition name="alert-dlg">
            <div v-if="open" class="alert-backdrop" @mousedown.self="emit('close')">
                <div ref="panel" class="alert-panel" role="alertdialog" aria-modal="true" :aria-labelledby="titleId" :aria-describedby="textId" tabindex="-1">
                    <div class="alert-icon" :class="tone"><i class="bi" :class="icon"></i></div>
                    <h2 :id="titleId" class="h5 fw-800 mb-2 text-break">{{ title }}</h2>
                    <p :id="textId" class="text-muted mb-4 alert-text">{{ message }}</p>
                    <div class="d-grid gap-2">
                        <button v-if="actionText" ref="actionBtn" type="button" class="btn btn-lg" :class="actionVariant === 'danger' ? 'btn-danger' : 'btn-primary'" @click="emit('action')">{{ actionText }}</button>
                        <button type="button" class="btn btn-ghost" @click="emit('close')">{{ closeText || t('common.close') }}</button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
/**
 * A small alert that pops up on top of everything, including other dialogs
 * (e.g. "Payment didn't start" over the vote dialog). Esc closes only this one.
 */
const props = withDefaults(defineProps<{
    open: boolean;
    title: string;
    message: string;
    tone?: 'danger' | 'warning' | 'info' | 'success';
    actionText?: string;
    closeText?: string;
    /** Red main button for destructive confirmations. */
    actionVariant?: 'primary' | 'danger';
    /** Override the tone's icon (Bootstrap icon class). */
    iconClass?: string;
}>(), { tone: 'danger', actionText: '', closeText: '', actionVariant: 'primary', iconClass: '' });
const emit = defineEmits<{ close: []; action: [] }>();

const titleId = useId();
const textId = useId();
const panel = ref<HTMLElement | null>(null);
const actionBtn = ref<HTMLButtonElement | null>(null);
const icon = computed(() => props.iconClass || ({ danger: 'bi-x-octagon-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill', success: 'bi-check-circle-fill' })[props.tone]);

// Capture phase + stop: the dialog underneath must not also close on Esc.
function onKey(e: KeyboardEvent) {
    if (props.open && e.key === 'Escape') {
        e.stopImmediatePropagation();
        e.preventDefault();
        emit('close');
    }
}
watch(() => props.open, async (o) => {
    if (!o) return;
    await nextTick();
    (actionBtn.value ?? panel.value)?.focus();
});
onMounted(() => window.addEventListener('keydown', onKey, true));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey, true));
</script>

<style scoped>
.alert-backdrop {
    position: fixed; inset: 0; z-index: 1700; display: flex; align-items: center; justify-content: center; padding: 1.25rem;
    background: rgba(10, 18, 34, .55); backdrop-filter: blur(3px);
}
.alert-panel {
    width: 100%; max-width: 360px; text-align: center; padding: 1.75rem 1.5rem 1.25rem;
    background: var(--surface); border-radius: 22px; box-shadow: var(--shadow-lg); outline: none;
}
.alert-text { white-space: pre-line; overflow-wrap: anywhere; }
.alert-icon {
    width: 64px; height: 64px; margin: 0 auto 1rem; border-radius: 50%; display: grid; place-items: center; font-size: 1.9rem;
}
.alert-icon.danger { background: var(--danger-50); color: var(--danger); }
.alert-icon.warning { background: var(--warning-50); color: var(--warning); }
.alert-icon.info { background: var(--brand-50); color: var(--brand-600); }
.alert-icon.success { background: var(--success-50); color: var(--success); }
.alert-dlg-enter-active, .alert-dlg-leave-active { transition: opacity .2s ease; }
.alert-dlg-enter-active .alert-panel { animation: pop-in .28s cubic-bezier(.2, .9, .3, 1.2); }
.alert-dlg-enter-from, .alert-dlg-leave-to { opacity: 0; }
@keyframes pop-in { from { transform: scale(.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
