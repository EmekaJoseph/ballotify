<template>
    <div class="toast-stack" aria-live="polite" aria-atomic="false">
        <TransitionGroup name="toast">
            <div v-for="t in toasts" :key="t.id" class="toast-b" :class="t.kind" role="status">
                <i class="bi" :class="icon[t.kind]"></i>
                <span class="flex-grow-1">{{ t.message }}</span>
                <button v-if="t.action" type="button" class="toast-action" @click="t.action.run()">{{ t.action.label }}</button>
                <button type="button" class="toast-close" aria-label="Dismiss" @click="dismiss(t.id)">
                    <i class="bi bi-x-lg"></i>
                </button>
                <!-- Time left to undo -->
                <span v-if="t.timeout" class="toast-timer" :style="{ animationDuration: t.timeout + 'ms' }"></span>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup lang="ts">
const { toasts, dismiss } = useToast();
const icon = { success: 'bi-check-circle-fill', error: 'bi-exclamation-octagon-fill', info: 'bi-info-circle-fill' } as const;
</script>

<style scoped>
.toast-stack {
    position: fixed; z-index: 2000; right: 16px; bottom: 16px; left: 16px;
    display: flex; flex-direction: column; align-items: flex-end; gap: 10px; pointer-events: none;
}
.toast-b {
    position: relative; overflow: hidden;
    pointer-events: auto; width: 100%; max-width: 380px;
    display: flex; align-items: flex-start; gap: .7rem; padding: .85rem 1rem;
    background: var(--night); color: #fff; border-radius: 14px; box-shadow: var(--shadow-lg);
    font-size: .92rem; font-weight: 500; line-height: 1.45;
}
.toast-b > .bi { font-size: 1.05rem; margin-top: 1px; }
.toast-b.success > .bi { color: #4ade80; }
.toast-b.error > .bi { color: #f87171; }
.toast-b.info > .bi { color: #5eead4; }
.toast-action {
    background: rgba(255, 255, 255, .12); border: 0; color: #5eead4; font-weight: 800; font-size: .82rem;
    padding: .15rem .6rem; border-radius: 8px; margin-top: -1px; flex-shrink: 0;
}
.toast-action:hover { background: rgba(255, 255, 255, .2); }
.toast-close { background: none; border: 0; color: rgba(255, 255, 255, .6); padding: 0 0 0 .25rem; font-size: .8rem; }
.toast-close:hover { color: #fff; }
.toast-timer {
    position: absolute; left: 0; bottom: 0; height: 3px; width: 100%; background: #5eead4; transform-origin: left;
    animation: toast-timer linear forwards;
}
@keyframes toast-timer { from { transform: scaleX(1); } to { transform: scaleX(0); } }
.toast-enter-active, .toast-leave-active { transition: all .25s var(--ease); }
.toast-enter-from { opacity: 0; transform: translateY(10px) scale(.98); }
.toast-leave-to { opacity: 0; transform: translateX(20px); }
</style>
