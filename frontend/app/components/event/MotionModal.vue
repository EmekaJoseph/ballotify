<template>
    <BaseModal :open="open" :title="category ? 'Edit motion' : 'Add a motion'"
        subtitle="Voters answer Yes, No or Abstain. Abstentions don’t count towards the majority." size="md" @close="emit('close')">
        <form id="motion-form" novalidate @submit.prevent="save">
            <div class="mb-3">
                <label for="mo-title" class="form-label">Motion</label>
                <input id="mo-title" v-model.trim="form.name" class="form-control" maxlength="255" placeholder="e.g. Approve the 2026 audited accounts" />
            </div>
            <div class="mb-3">
                <label for="mo-text" class="form-label">Full text <span class="text-muted fw-normal">(optional)</span></label>
                <textarea id="mo-text" v-model="form.description" class="form-control" rows="4" maxlength="2000"
                    placeholder="e.g. That the audited accounts for the year ended 31 December 2026 be adopted."></textarea>
                <div class="form-text text-end">{{ form.description.length }}/2000</div>
            </div>
            <div>
                <label for="mo-rule" class="form-label">Passes with</label>
                <AppSelect id="mo-rule" v-model="form.pass_rule" :options="RULES" :disabled="locked" :title="locked ? 'Locked while voting is under way' : ''" />
            </div>
            <div v-if="error" class="field-error mt-3">{{ error }}</div>
        </form>
        <template #footer>
            <button type="button" class="btn btn-ghost" @click="emit('close')">Cancel</button>
            <button type="submit" form="motion-form" class="btn btn-primary" :disabled="busy || form.name.length < 2">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span> {{ category ? 'Save' : 'Add motion' }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { Category, PassRule } from '~/types';

const props = defineProps<{ open: boolean; eventId: number; category: Category | null; locked?: boolean }>();
const emit = defineEmits<{ close: []; saved: [] }>();
const toast = useToast();

const RULES: { value: PassRule; label: string; hint: string }[] = [
    { value: 'simple', label: 'Simple majority', hint: 'More Yes than No' },
    { value: 'two_thirds', label: 'Two-thirds majority', hint: 'At least 2 in 3 of the Yes and No votes are Yes' },
    { value: 'three_quarters', label: 'Three-quarters majority', hint: 'At least 3 in 4 of the Yes and No votes are Yes' },
];
const form = reactive({ name: '', description: '', pass_rule: 'simple' as PassRule });
const busy = ref(false);
const error = ref('');

watch(() => props.open, (o) => {
    if (!o) return;
    form.name = props.category?.name ?? '';
    form.description = props.category?.description ?? '';
    form.pass_rule = props.category?.pass_rule ?? 'simple';
    error.value = '';
});

async function save() {
    busy.value = true;
    error.value = '';
    try {
        const data = { name: form.name, description: form.description.trim() || null, pass_rule: form.pass_rule };
        if (props.category) {
            // The pass rule locks with the ballot; the wording can still be corrected (and is logged).
            const changes: Record<string, unknown> = {};
            if (data.name !== props.category.name) changes.name = data.name;
            if (data.description !== (props.category.description ?? null)) changes.description = data.description;
            if (!props.locked && data.pass_rule !== props.category.pass_rule) changes.pass_rule = data.pass_rule;
            if (Object.keys(changes).length) await adminApi.updateCategory(props.eventId, props.category.id, changes);
            toast.success('Motion saved');
        } else {
            await adminApi.createCategory(props.eventId, { ...data, method: 'motion' });
            toast.success('Motion added');
        }
        emit('saved');
    } catch (e) {
        error.value = Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}
</script>
