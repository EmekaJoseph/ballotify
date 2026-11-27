<template>
    <BaseModal :open="open" title="Reset votes and results?" size="md" @close="emit('close')">
        <p class="mb-2">For test runs: start the real election clean.</p>
        <ul class="small mb-3 ps-3">
            <li>Every vote, ranking and receipt is erased, and the results go back to zero.</li>
            <li>Every voter can vote again <b>with the same code</b>. Voters, codes and the ballot stay as they are.</li>
            <li>It can’t be undone. It is recorded for good: the Activity log, the results, the PDF report and any certificate will say votes were reset, when and by whom.</li>
        </ul>
        <label for="reset-confirm" class="form-label small fw-semibold">Type the event name to confirm: <span class="text-ink">{{ eventName }}</span></label>
        <input id="reset-confirm" v-model="typed" class="form-control" autocomplete="off" spellcheck="false" :disabled="busy" @keydown.enter.prevent="submit" />
        <div v-if="error" class="field-error mt-2">{{ error }}</div>

        <template #footer>
            <button class="btn btn-ghost" :disabled="busy" @click="emit('close')">Cancel</button>
            <button class="btn btn-danger" :disabled="busy || !matches" @click="submit">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span> Reset votes
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';

const props = defineProps<{ open: boolean; eventId: number; eventName: string }>();
const emit = defineEmits<{ close: []; done: [erased: number] }>();
const toast = useToast();

const typed = ref('');
const busy = ref(false);
const error = ref('');
const matches = computed(() => typed.value.trim().toLowerCase() === props.eventName.trim().toLowerCase());
watch(() => props.open, (o) => { if (o) { typed.value = ''; error.value = ''; } });

async function submit() {
    if (!matches.value || busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.resetVotes(props.eventId, typed.value);
        toast.success(data.message);
        emit('done', data.erased);
    } catch (e) {
        error.value = Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}
</script>
