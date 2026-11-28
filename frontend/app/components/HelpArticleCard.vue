<template>
    <article class="card-b help-article" :class="{ open }">
        <button type="button" class="head" :aria-expanded="open" @click="emit('toggle')">
            <span class="min-w-0 flex-grow-1 text-start">
                <span class="d-block fw-bold text-ink">{{ article.title }}</span>
                <span class="d-block small text-muted">{{ article.summary }}</span>
            </span>
            <i class="bi bi-chevron-down caret" aria-hidden="true"></i>
        </button>
        <div v-if="open" class="body">
            <ol v-if="article.steps?.length" class="mb-0">
                <li v-for="(s, i) in article.steps" :key="i">{{ s }}</li>
            </ol>
            <div v-for="(tip, i) in article.tips ?? []" :key="i" class="tip">{{ tip }}</div>
        </div>
    </article>
</template>

<script setup lang="ts">
import type { HelpArticle } from '~/utils/helpArticles';

defineProps<{ article: HelpArticle; open?: boolean }>();
const emit = defineEmits<{ toggle: [] }>();
</script>

<style scoped>
.help-article { padding: 0; overflow: hidden; }
.head { width: 100%; display: flex; align-items: center; gap: 1rem; padding: .9rem 1.1rem; background: none; border: 0; cursor: pointer; }
.head:hover { background: var(--surface-2); }
.caret { color: var(--muted); transition: transform .2s ease; }
.open .caret { transform: rotate(180deg); color: var(--brand-600); }
.body { padding: .25rem 1.1rem 1.1rem 1.1rem; border-top: 1px solid var(--line); }
.body ol { padding-left: 1.2rem; margin-top: .75rem; }
.body li { margin-bottom: .45rem; line-height: 1.55; color: var(--ink); }
.tip { margin-top: .75rem; padding: .6rem .8rem; border-radius: 10px; background: var(--brand-50); color: var(--ink); font-size: .9rem; }
.tip .bi { color: var(--brand-600); }
</style>
