<template>
    <!-- A translated sentence whose {placeholders} can be filled with markup via named slots,
         e.g. <Tx k="vote.noCode"><template #label><b>Membership number</b></template></Tx> -->
    <span><template v-for="(part, i) in parts" :key="i"><slot v-if="part.slot" :name="part.slot" /><template v-else>{{ part.text }}</template></template></span>
</template>

<script setup lang="ts">
import type { MessageKey } from '~/i18n/messages';

const props = defineProps<{ k: MessageKey; params?: Record<string, string | number> }>();
const slots = useSlots();

const parts = computed(() => {
    const raw = t(props.k);
    const out: { text?: string; slot?: string }[] = [];
    const re = /\{(\w+)\}/g;
    let last = 0;
    let m: RegExpExecArray | null;
    while ((m = re.exec(raw))) {
        if (m.index > last) out.push({ text: raw.slice(last, m.index) });
        const name = m[1]!;
        if (slots[name]) out.push({ slot: name });
        else out.push({ text: props.params && name in props.params ? String(props.params[name]) : m[0] });
        last = re.lastIndex;
    }
    if (last < raw.length) out.push({ text: raw.slice(last) });
    return out;
});
</script>
