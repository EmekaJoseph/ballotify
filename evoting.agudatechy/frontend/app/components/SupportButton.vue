<template>
    <!-- Floating "Chat with support": dashboard, website and sign-in pages (not voting pages). Hidden when no number is set. -->
    <a v-if="support.whatsapp" :href="support.whatsappHref(context)" target="_blank" rel="noopener" class="support-fab"
        title="Chat with Agudatechy support on WhatsApp" aria-label="Chat with Agudatechy support on WhatsApp">
        <i class="bi bi-whatsapp"></i><span class="label">Help</span>
    </a>
</template>

<script setup lang="ts">
defineProps<{ context?: string }>();
const support = useSupport();
</script>

<style scoped>
.support-fab {
    position: fixed; right: 1.1rem; bottom: calc(1.1rem + env(safe-area-inset-bottom)); z-index: 1040;
    display: inline-flex; align-items: center; gap: .45rem; height: 48px; padding: 0 1.05rem 0 .95rem; border-radius: 999px;
    background: #25d366; color: #fff; font-weight: 800; text-decoration: none; font-size: .95rem;
    box-shadow: 0 10px 28px -8px rgba(37, 211, 102, .7), 0 2px 6px rgba(15, 23, 42, .15);
    transition: transform .15s ease, box-shadow .15s ease;
}
.support-fab .bi { font-size: 1.35rem; }
.support-fab:hover { color: #fff; transform: translateY(-2px); }
@media print { .support-fab { display: none; } }
@media (max-width: 575.98px) { .support-fab .label { display: none; } .support-fab { width: 52px; padding: 0; justify-content: center; } }
</style>
