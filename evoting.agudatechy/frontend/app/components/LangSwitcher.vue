<template>
    <AppSelect :model-value="ballotLang" :options="options" variant="pill" icon="bi-translate" :block="false" :menu-min-width="150"
        :aria-label="t('common.language')" :title="t('common.language')" class="lang-switch" @change="pick" />
</template>

<script setup lang="ts">
const options = LANGUAGES.map(l => ({ value: l.code as string, label: l.name }));
function pick(code: string) {
    if (isLang(code)) setBallotLang(code);
}
</script>

<style scoped>
.lang-switch :deep(.as-trigger) { max-width: 150px; }
</style>
