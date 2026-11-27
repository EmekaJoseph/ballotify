<template>
    <!-- "Are you still there?" before an idle ballot closes. Any tap or key dismisses it. -->
    <Teleport to="body">
        <Transition name="idle-fade">
            <div v-if="show" class="idle-backdrop" role="alertdialog" aria-modal="true" aria-labelledby="idle-title" aria-describedby="idle-text">
                <div class="idle-panel">
                    <div class="idle-ring" aria-hidden="true">
                        <svg viewBox="0 0 64 64"><circle cx="32" cy="32" r="28" class="track" /><circle cx="32" cy="32" r="28" class="bar" :style="{ strokeDashoffset: dash }" /></svg>
                        <span class="secs">{{ seconds }}</span>
                    </div>
                    <h2 id="idle-title" class="h5 fw-800 mb-1">{{ t('idle.title') }}</h2>
                    <p id="idle-text" class="text-muted small mb-3">{{ t('idle.text', { n: seconds }) }}</p>
                    <button ref="stayBtn" type="button" class="btn btn-primary w-100" @click="emit('stay')">{{ t('idle.stay') }}</button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
const props = defineProps<{ show: boolean; seconds: number; total: number }>();
const emit = defineEmits<{ stay: [] }>();
const stayBtn = ref<HTMLButtonElement | null>(null);
const CIRC = 2 * Math.PI * 28;
const dash = computed(() => CIRC * (1 - Math.min(1, props.seconds / Math.max(1, props.total))));
watch(() => props.show, async (s) => { if (s) { await nextTick(); stayBtn.value?.focus(); } });
</script>

<style scoped>
.idle-backdrop {
    position: fixed; inset: 0; z-index: 1600; display: flex; align-items: center; justify-content: center; padding: 1.25rem;
    background: rgba(10, 18, 34, .55); backdrop-filter: blur(4px);
}
.idle-panel {
    width: 100%; max-width: 360px; text-align: center; padding: 1.75rem 1.5rem 1.5rem; border-radius: 20px;
    background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-lg);
}
.idle-ring { position: relative; width: 72px; height: 72px; margin: 0 auto 1rem; }
.idle-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.idle-ring circle { fill: none; stroke-width: 5; }
.idle-ring .track { stroke: var(--line); }
.idle-ring .bar { stroke: #d97706; stroke-linecap: round; stroke-dasharray: 175.93; transition: stroke-dashoffset 1s linear; }
.idle-ring .secs { position: absolute; inset: 0; display: grid; place-items: center; font-size: 1.4rem; font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; }
.idle-fade-enter-active, .idle-fade-leave-active { transition: opacity .2s ease; }
.idle-fade-enter-from, .idle-fade-leave-to { opacity: 0; }
</style>
