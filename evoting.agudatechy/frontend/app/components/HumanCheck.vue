<template>
    <!-- Cloudflare Turnstile: usually invisible or one tap. Renders nothing when no site key is set. -->
    <div v-if="siteKey" ref="box" class="human-check"></div>
</template>

<script setup lang="ts">
/**
 * v-model is the Turnstile token ('' until the check passes). Call reset()
 * after a failed submit: each token works once.
 */
declare global {
    interface Window {
        turnstile?: {
            render: (el: HTMLElement, opts: Record<string, unknown>) => string;
            reset: (id?: string) => void;
            remove: (id?: string) => void;
        };
        __turnstileLoading?: Promise<void>;
    }
}

const token = defineModel<string>({ default: '' });
const siteKey = String(useRuntimeConfig().public.turnstileSiteKey || '');
const box = ref<HTMLElement | null>(null);
let widgetId: string | undefined;

function loadScript(): Promise<void> {
    if (window.turnstile) return Promise.resolve();
    window.__turnstileLoading ??= new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        s.async = true;
        s.onload = () => resolve();
        s.onerror = () => reject(new Error('Turnstile failed to load'));
        document.head.appendChild(s);
    });
    return window.__turnstileLoading;
}

onMounted(async () => {
    if (!siteKey) return;
    try {
        await loadScript();
        if (!box.value || !window.turnstile) return;
        widgetId = window.turnstile.render(box.value, {
            sitekey: siteKey,
            // Turnstile speaks French and English; for Yorùbá, Hausa and Igbo it follows the browser.
            language: 'en',
            theme: 'light',
            callback: (t: string) => { token.value = t; },
            'expired-callback': () => { token.value = ''; },
            'error-callback': () => { token.value = ''; },
        });
    } catch {
        // Blocked by an extension or network: the server explains if it's required.
    }
});
onBeforeUnmount(() => { if (widgetId) window.turnstile?.remove(widgetId); });

/** Whether the form may be sent (always true when the check is off). */
const ready = computed(() => !siteKey || !!token.value);
function reset() {
    token.value = '';
    if (widgetId) window.turnstile?.reset(widgetId);
}
defineExpose({ ready, reset });
</script>

<style scoped>
.human-check { min-height: 65px; display: flex; justify-content: center; }
</style>
