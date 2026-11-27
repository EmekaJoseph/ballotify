<template>
    <div class="container py-5" style="max-width: 820px">
        <span class="eyebrow mb-3">Legal</span>
        <h1 class="section-title mb-2">{{ title }}</h1>
        <p class="text-muted mb-5">Last updated {{ updated }}</p>
        <article class="legal card-b pad">
            <slot />
            <div class="divider my-4"></div>
            <p class="small text-muted mb-0">
                Questions? Contact {{ company }}<template v-if="contactEmail"> at <a :href="`mailto:${contactEmail}`">{{ contactEmail }}</a></template>.
            </p>
        </article>
    </div>
</template>

<script setup lang="ts">
defineProps<{ title: string; updated: string }>();
const config = useRuntimeConfig();
const company = computed(() => String(config.public.companyName || 'E-voting by Agudatechy'));
const contactEmail = computed(() => String(config.public.contactEmail || ''));
</script>

<style scoped>
.legal :deep(h2) { font-size: 1.15rem; font-weight: 800; margin: 1.75rem 0 .6rem; }
.legal :deep(h2:first-child) { margin-top: 0; }
.legal :deep(p), .legal :deep(li) { color: var(--ink-2); line-height: 1.75; }
.legal :deep(ul) { padding-left: 1.2rem; }
</style>
