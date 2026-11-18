<template>
    <BaseModal :open="open" :title="voter ? 'Edit voter' : 'Add a voter'"
        :subtitle="voter ? '' : 'A unique code is created automatically unless you type one.'" @close="emit('close')">
        <form id="voter-form" novalidate @submit.prevent="submit">
            <div class="mb-3">
                <label for="vf-name" class="form-label">Full name</label>
                <input id="vf-name" v-model.trim="form.name" class="form-control" maxlength="255" placeholder="e.g. Ada Obi"
                    :class="{ 'is-invalid': errors.name }" />
                <div v-if="errors.name" class="field-error">{{ errors.name }}</div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label for="vf-email" class="form-label">Email</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input id="vf-email" v-model.trim="form.email" type="email" class="form-control" maxlength="255"
                            placeholder="ada@example.com" autocomplete="off" :class="{ 'is-invalid': errors.email }" />
                    </div>
                    <div v-if="errors.email" class="field-error">{{ errors.email }}</div>
                </div>
                <div class="col-sm-6">
                    <label for="vf-phone" class="form-label">Phone</label>
                    <div class="input-icon">
                        <i class="bi bi-phone"></i>
                        <input id="vf-phone" v-model.trim="form.phone" type="tel" class="form-control" maxlength="32"
                            placeholder="+234 801 234 5678" autocomplete="off" :class="{ 'is-invalid': errors.phone }" />
                    </div>
                    <div v-if="errors.phone" class="field-error">{{ errors.phone }}</div>
                </div>
            </div>
            <div>
                <label for="vf-code" class="form-label">Voting code <span class="text-muted fw-normal">{{ voter ? '' : '(optional)' }}</span></label>
                <input id="vf-code" v-model.trim="form.code" class="form-control mono text-uppercase" maxlength="64"
                    :placeholder="voter ? '' : 'Leave empty to generate'" :class="{ 'is-invalid': errors.code }" />
                <div v-if="errors.code" class="field-error">{{ errors.code }}</div>
                <div v-else-if="voter?.invited_at" class="form-text">Changing the code means the invitation already sent will no longer work.</div>
            </div>
            <div v-if="generalError" class="field-error mt-3">{{ generalError }}</div>
        </form>
        <template #footer>
            <button type="button" class="btn btn-ghost" @click="emit('close')">Cancel</button>
            <button type="submit" form="voter-form" class="btn btn-primary" :disabled="busy">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                {{ voter ? 'Save changes' : 'Add voter' }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { Voter } from '~/types';

const props = defineProps<{ open: boolean; eventId: number; voter: Voter | null }>();
const emit = defineEmits<{ close: []; saved: [voter: Voter, created: boolean] }>();

const form = reactive({ name: '', email: '', phone: '', code: '' });
const errors = ref<Record<string, string>>({});
const generalError = ref('');
const busy = ref(false);

watch(() => props.open, (open) => {
    if (!open) return;
    form.name = props.voter?.name ?? '';
    form.email = props.voter?.email ?? '';
    form.phone = props.voter?.phone ?? '';
    form.code = props.voter?.code ?? '';
    errors.value = {};
    generalError.value = '';
});

async function submit() {
    errors.value = {};
    generalError.value = '';
    if (form.email && !/^\S+@\S+\.\S+$/.test(form.email)) { errors.value.email = 'Enter a valid email address.'; return; }
    if (props.voter && form.code.length < 4) { errors.value.code = 'Codes need at least 4 characters.'; return; }

    busy.value = true;
    try {
        const payload = { name: form.name || null, email: form.email || null, phone: form.phone || null };
        const { data } = props.voter
            ? await adminApi.updateVoter(props.eventId, props.voter.id, { ...payload, code: form.code })
            : await adminApi.createVoter(props.eventId, { ...payload, code: form.code || null });
        emit('saved', data, !props.voter);
    } catch (e) {
        errors.value = apiFieldErrors(e);
        if (!Object.keys(errors.value).length) generalError.value = apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}
</script>
