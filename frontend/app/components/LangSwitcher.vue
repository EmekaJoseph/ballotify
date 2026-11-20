<template>
    <label class="lang-switch" :title="t('common.language')">
        <i class="bi bi-translate" aria-hidden="true"></i>
        <select :value="ballotLang" :aria-label="t('common.language')" @change="pick">
            <option v-for="l in LANGUAGES" :key="l.code" :value="l.code" :lang="l.code">{{ l.name }}</option>
        </select>
        <i class="bi bi-chevron-down caret" aria-hidden="true"></i>
    </label>
</template>

<script setup lang="ts">
function pick(e: Event) {
    const code = (e.target as HTMLSelectElement).value;
    if (isLang(code)) setBallotLang(code);
}
</script>

<style scoped>
.lang-switch {
    position: relative; display: inline-flex; align-items: center; gap: .3rem; height: 36px; padding: 0 .55rem 0 .65rem;
    border: 1px solid var(--line); border-radius: 999px; background: var(--surface); color: var(--muted); cursor: pointer;
}
.lang-switch:hover, .lang-switch:focus-within { border-color: var(--line-strong); color: var(--ink); }
.lang-switch:focus-within { box-shadow: 0 0 0 3px var(--ring, rgba(14, 124, 119, .2)); }
.lang-switch select {
    appearance: none; -webkit-appearance: none; border: 0; background: transparent; color: var(--ink);
    font-size: .85rem; font-weight: 600; padding: 0 1rem 0 0; cursor: pointer; outline: none; max-width: 92px;
}
.lang-switch .caret { position: absolute; right: .55rem; font-size: .65rem; pointer-events: none; }
.lang-switch select option { color: #0f172a; }
</style>
