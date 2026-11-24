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
            <div class="mb-3">
                <label for="vf-member" class="form-label">{{ memberLabel }} <span class="text-muted fw-normal">(optional)</span></label>
                <input id="vf-member" v-model.trim="form.member_id" class="form-control mono text-uppercase" maxlength="64"
                    placeholder="e.g. MEM-0042" autocomplete="off" :class="{ 'is-invalid': errors.member_id }" />
                <div v-if="errors.member_id" class="field-error">{{ errors.member_id }}</div>
                <div v-else class="form-text">Lets this voter request their own code, if you switch on self-registration.</div>
            </div>
            <div class="mb-3">
                <label for="vf-groups" class="form-label">Groups <span class="text-muted fw-normal">(optional)</span></label>
                <input id="vf-groups" v-model="form.groups" class="form-control" maxlength="300" placeholder="e.g. Lagos branch, Youth" autocomplete="off"
                    :list="groupOptions.length ? 'vf-group-list' : undefined" :class="{ 'is-invalid': errors.groups }" />
                <datalist v-if="groupOptions.length" id="vf-group-list"><option v-for="g in groupOptions" :key="g" :value="g" /></datalist>
                <div v-if="errors.groups" class="field-error">{{ errors.groups }}</div>
                <div v-else class="form-text">Separate with commas. Categories limited to a group only show to its voters.</div>
            </div>
            <div v-if="rules?.weighted" class="mb-3">
                <label for="vf-weight" class="form-label">Weight <span class="text-muted fw-normal">(shares, units…)</span></label>
                <input id="vf-weight" v-model.number="form.weight" type="number" min="1" max="10000000" step="1" class="form-control" style="max-width: 200px"
                    :disabled="weightLocked" :class="{ 'is-invalid': errors.weight }" />
                <div v-if="errors.weight" class="field-error">{{ errors.weight }}</div>
                <div v-else-if="weightLocked" class="form-text"><i class="bi bi-lock"></i> Weights are fixed once voting starts.</div>
                <div v-else class="form-text">This voter’s ballot counts as this many votes.</div>
            </div>
            <div v-if="rules?.proxies && voter" class="mb-3">
                <label class="form-label">Proxy <span class="text-muted fw-normal">(optional)</span></label>
                <div v-if="voter.used_at" class="proxy-note"><i class="bi bi-check2-circle"></i> {{ voter.proxy_voter_id ? `Voted by proxy (${nameOf(voter.proxy_voter_id)}).` : 'Has voted, so their proxy can’t change.' }}</div>
                <div v-else-if="heldFor.length" class="proxy-note">
                    <i class="bi bi-person-check"></i> Holds proxies for <b>{{ heldFor.join(', ') }}</b>, so votes for them and can’t give their own vote away.
                </div>
                <template v-else>
                    <AppSelect v-model="form.proxy" :options="proxyOptions" searchable placeholder="Votes for themselves" aria-label="Proxy" />
                    <div v-if="errors.proxy_voter_id" class="field-error">{{ errors.proxy_voter_id }}</div>
                    <div v-else class="form-text">
                        Who votes for this member if they can’t. Their own code then stops working, and the holder’s ballot counts for both.
                    </div>
                </template>
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

/** Meeting rules switched on for this event (weighted votes, proxies). */
export interface VoterFormRules { weighted: boolean; proxies: boolean; maxProxies: number; locked: boolean; voters: Voter[] }

const props = withDefaults(defineProps<{ open: boolean; eventId: number; voter: Voter | null; memberLabel?: string; groupOptions?: string[]; rules?: VoterFormRules | null }>(), { memberLabel: 'Member ID', groupOptions: () => [], rules: null });
const emit = defineEmits<{ close: []; saved: [voter: Voter, created: boolean]; limit: [] }>();

const form = reactive({ name: '', email: '', phone: '', member_id: '', code: '', groups: '', weight: 1 as number | '', proxy: '' as string });
const errors = ref<Record<string, string>>({});
const generalError = ref('');
const busy = ref(false);

watch(() => props.open, (open) => {
    if (!open) return;
    form.name = props.voter?.name ?? '';
    form.email = props.voter?.email ?? '';
    form.phone = props.voter?.phone ?? '';
    form.member_id = props.voter?.member_id ?? '';
    form.code = props.voter?.code ?? '';
    form.groups = (props.voter?.groups ?? []).join(', ');
    form.weight = props.voter?.weight ?? 1;
    form.proxy = props.voter?.proxy_voter_id ? String(props.voter.proxy_voter_id) : '';
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
        const groups = form.groups.split(/[,;|]/).map(g => g.trim()).filter(Boolean);
        const payload: Record<string, unknown> = { name: form.name || null, email: form.email || null, phone: form.phone || null, member_id: form.member_id || null, groups };
        if (props.rules?.weighted && !weightLocked.value) payload.weight = Number(form.weight) || 1;
        const proxyId = form.proxy ? Number(form.proxy) : null;
        if (props.voter && props.rules?.proxies && proxyId !== (props.voter.proxy_voter_id ?? null)) payload.proxy_voter_id = proxyId;
        const { data } = props.voter
            ? await adminApi.updateVoter(props.eventId, props.voter.id, { ...payload, code: form.code })
            : await adminApi.createVoter(props.eventId, { ...payload, code: form.code || null });
        emit('saved', data, !props.voter);
    } catch (e) {
        if (planLimitReason(e) === 'events') { emit('limit'); return; }
        errors.value = apiFieldErrors(e);
        if (!Object.keys(errors.value).length) generalError.value = apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}

const weightLocked = computed(() => !!props.rules?.locked && !!props.voter);
const nameOf = (id: number) => props.rules?.voters.find(v => v.id === id)?.name || 'a member';
/** Members this voter already holds a proxy for. */
const heldFor = computed(() => (props.voter && props.rules ? props.rules.voters.filter(v => v.proxy_voter_id === props.voter!.id).map(v => v.name || 'a member') : []));
/** Who can hold this member's proxy: anyone who hasn't voted and hasn't given their own vote away, with room left. */
const proxyOptions = computed(() => {
    const r = props.rules;
    if (!r || !props.voter) return [];
    const held = new Map<number, number>();
    for (const v of r.voters) if (v.proxy_voter_id && v.id !== props.voter.id) held.set(v.proxy_voter_id, (held.get(v.proxy_voter_id) ?? 0) + 1);
    const opts = r.voters
        .filter(v => v.id !== props.voter!.id && !v.used_at && !v.proxy_voter_id)
        .map(v => {
            const n = held.get(v.id) ?? 0;
            return { value: String(v.id), label: v.name || `Code ${v.code}`, hint: n ? `holds ${n}/${r.maxProxies}` : undefined, disabled: n >= r.maxProxies };
        });
    return [{ value: '', label: 'Votes for themselves (no proxy)' }, ...opts];
});
</script>

<style scoped>
.proxy-note { padding: .6rem .8rem; border-radius: 12px; background: var(--surface-2); font-size: .875rem; color: var(--ink-2); overflow-wrap: anywhere; }
</style>
