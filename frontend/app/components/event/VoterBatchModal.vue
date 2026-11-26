<template>
    <BaseModal :open="open" title="Add voters" subtitle="Fill in one line and press Enter. Add as many as you like, then create all their codes at once."
        size="lg" @close="close">
        <!-- One-line entry. Enter adds it to the list below. -->
        <form class="entry" :class="{ weighted }" novalidate @submit.prevent="addRow">
            <div>
                <label class="form-label small mb-1" for="vb-name">Name</label>
                <input id="vb-name" ref="nameInput" v-model="draft.name" class="form-control" maxlength="255" placeholder="e.g. Ada Obi" autocomplete="off" />
            </div>
            <div>
                <label class="form-label small mb-1" for="vb-email">Email</label>
                <input id="vb-email" v-model="draft.email" type="email" class="form-control" maxlength="255" placeholder="optional" autocomplete="off" />
            </div>
            <div>
                <label class="form-label small mb-1" for="vb-phone">Phone</label>
                <input id="vb-phone" v-model="draft.phone" type="tel" class="form-control" maxlength="32" placeholder="optional" autocomplete="off" />
            </div>
            <div>
                <label class="form-label small mb-1 text-truncate d-block" for="vb-id">{{ memberLabel }}</label>
                <input id="vb-id" v-model="draft.memberId" class="form-control" maxlength="64" placeholder="optional" autocomplete="off" />
            </div>
            <div>
                <label class="form-label small mb-1" for="vb-group">Group</label>
                <input id="vb-group" v-model="draft.groups" class="form-control" maxlength="255" placeholder="optional" autocomplete="off" :list="groupOptions.length ? 'vb-groups' : undefined" />
                <datalist v-if="groupOptions.length" id="vb-groups"><option v-for="g in groupOptions" :key="g" :value="g" /></datalist>
            </div>
            <div v-if="weighted">
                <label class="form-label small mb-1" for="vb-weight">Weight</label>
                <input id="vb-weight" v-model.number="draft.weight" type="number" min="1" max="10000000" class="form-control" placeholder="1" />
            </div>
            <button type="submit" class="btn btn-soft f-add" :disabled="!canAdd"><i class="bi bi-plus-lg"></i> Add</button>
        </form>
        <div v-if="entryError" class="field-error mt-2" role="alert">{{ entryError }}</div>
        <div class="form-text">
            Press <kbd>Enter</kbd> to add. A name, email or phone is enough; give an email or phone to send codes by message.
            Several groups: separate them with “;”.
        </div>

        <!-- The list waiting to be saved -->
        <div class="mt-3 border rounded-3 overflow-hidden">
            <div v-if="!rows.length" class="text-center text-muted small py-4">Nothing added yet.</div>
            <div v-else class="table-responsive">
                <table class="table-b small mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px">#</th><th>Name</th><th>Email</th><th>Phone</th><th>{{ memberLabel }}</th><th>Group</th>
                            <th v-if="weighted" class="text-end">Weight</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, i) in rows" :key="r.key">
                            <td class="text-muted">{{ i + 1 }}</td>
                            <td class="fw-semibold text-ink text-break">
                                {{ r.name || '—' }}
                                <i v-if="r.note" class="bi bi-files text-warning ms-1" :title="r.note"></i>
                            </td>
                            <td class="text-break">{{ r.email || '—' }}</td>
                            <td class="text-nowrap">{{ r.phone || '—' }}</td>
                            <td class="text-break">{{ r.memberId || '—' }}</td>
                            <td class="text-break">{{ r.groups || '—' }}</td>
                            <td v-if="weighted" class="text-end">{{ r.weight ?? 1 }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-ghost btn-icon btn-sm" :aria-label="`Remove ${r.name || 'row ' + (i + 1)}`" title="Remove" @click="rows.splice(i, 1)">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div v-if="error" class="field-error mt-2">{{ error }}</div>

        <template #footer>
            <span class="small text-muted me-auto align-self-center">{{ rows.length }} to add</span>
            <button class="btn btn-ghost" :disabled="busy" @click="close">Cancel</button>
            <button class="btn btn-primary" :disabled="busy || !rows.length" @click="save">
                <span v-if="busy" class="spinner-border spinner-border-sm"></span>
                Create {{ rows.length || '' }} code{{ rows.length === 1 ? '' : 's' }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { GeneratedCode, Voter } from '~/types';

const props = withDefaults(defineProps<{
    open: boolean;
    eventId: number;
    /** Voters already on the list (to refuse duplicates). */
    existing?: Voter[];
    memberLabel?: string;
    weighted?: boolean;
    /** Groups already used on this event (suggested in the Group field). */
    groupOptions?: string[];
}>(), { existing: () => [], memberLabel: 'Member ID', weighted: false, groupOptions: () => [] });
const emit = defineEmits<{ close: []; created: [codes: GeneratedCode[]]; limit: [] }>();
const { confirm } = useConfirm();

interface Row { key: number; name: string; email: string; phone: string; memberId: string; groups: string; weight: number | null; note: string }
const rows = ref<Row[]>([]);
const blank = () => ({ name: '', email: '', phone: '', memberId: '', groups: '', weight: null as number | null });
const draft = reactive(blank());
const entryError = ref('');
const error = ref('');
const busy = ref(false);
const nameInput = ref<HTMLInputElement | null>(null);
let nextKey = 1;

const canAdd = computed(() => !!(draft.name.trim() || draft.email.trim() || draft.phone.trim()));
const idKey = (id: string) => id.trim().toUpperCase();

watch(() => props.open, (o) => {
    if (!o) return;
    rows.value = [];
    entryError.value = '';
    error.value = '';
    Object.assign(draft, blank());
    nextTick(() => nameInput.value?.focus());
});
watch(() => [draft.name, draft.email, draft.phone, draft.memberId], () => { entryError.value = ''; });

function addRow() {
    if (!canAdd.value) return;
    const row: Row = {
        key: nextKey++,
        name: draft.name.replace(/\s+/g, ' ').trim(),
        email: draft.email.trim(),
        phone: draft.phone.trim(),
        memberId: draft.memberId.trim(),
        groups: draft.groups.trim(),
        weight: props.weighted && draft.weight && draft.weight > 1 ? Math.floor(draft.weight) : null,
        note: '',
    };
    if (row.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(row.email)) {
        entryError.value = `“${row.email}” doesn’t look like an email address.`;
        return;
    }
    // Same ID: refused (the server would refuse it too).
    if (row.memberId) {
        const id = idKey(row.memberId);
        if (rows.value.some(r => r.memberId && idKey(r.memberId) === id)) { entryError.value = `${props.memberLabel} ${row.memberId} is already in your list.`; return; }
        if (props.existing.some(v => v.member_id && idKey(v.member_id) === id)) { entryError.value = `${props.memberLabel} ${row.memberId} is already on the voter list.`; return; }
    }
    // Same person (same name and contact) or same email: refused, they'd get two codes.
    // Same name only, or a shared phone: allowed (often two people, or a family phone), but marked.
    const contact = { name: row.name, email: row.email, phone: row.phone };
    const inList = matchesIn(contact, rows.value, r => r.name || r.email || r.phone);
    const onFile = matchesIn(contact, props.existing, v => v.name || v.email || v.phone || `code ${v.code}`);
    const hard = [...inList.map(m => ({ ...m, where: 'in your list' })), ...onFile.map(m => ({ ...m, where: 'on the voter list' }))]
        .find(m => m.kind === 'same' || m.kind === 'email');
    if (hard) {
        entryError.value = hard.kind === 'same'
            ? `${row.name} is already ${hard.where} with the same contact details.`
            : `${row.email} is already ${hard.where} (${hard.with}).`;
        return;
    }
    const soft = [...inList, ...onFile][0];
    if (soft) row.note = describeMatch(soft);

    rows.value.push(row);
    Object.assign(draft, { ...blank(), groups: draft.groups, weight: draft.weight }); // keep group and weight for the next person
    nextTick(() => nameInput.value?.focus());
}

async function save() {
    // Something typed but not added yet: add it too, rather than lose it.
    if (canAdd.value) addRow();
    if (entryError.value || !rows.value.length) return;
    busy.value = true;
    error.value = '';
    try {
        const { data } = await adminApi.importVoters(props.eventId, {
            voters: rows.value.map(r => ({
                name: r.name || undefined, email: r.email || undefined, phone: r.phone || undefined,
                member_id: r.memberId || undefined, groups: r.groups || undefined, ...(r.weight ? { weight: r.weight } : {}),
            })),
        });
        rows.value = [];
        emit('created', data.voters);
    } catch (e) {
        if (planLimitReason(e) === 'events') { emit('limit'); return; }
        error.value = Object.values(apiFieldErrors(e))[0] ?? apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}

async function close() {
    if (rows.value.length && !busy.value) {
        const ok = await confirm({
            title: 'Discard this list?',
            message: `${rows.value.length} voter${rows.value.length === 1 ? ' hasn’t' : 's haven’t'} been saved yet.`,
            confirmText: 'Discard', danger: true,
        });
        if (!ok) return;
    }
    emit('close');
}
</script>

<style scoped>
.entry { display: grid; grid-template-columns: 1.3fr 1.3fr 1fr 1fr 1fr auto; gap: .6rem; align-items: end; }
.entry.weighted { grid-template-columns: 1.3fr 1.3fr 1fr 1fr 1fr .6fr auto; }
.entry .form-control { min-width: 0; }
.f-add { height: 46px; white-space: nowrap; }
@media (max-width: 991.98px) {
    .entry, .entry.weighted { grid-template-columns: 1fr 1fr; }
    .f-add { grid-column: 1 / -1; width: 100%; }
}
@media (max-width: 575.98px) { .entry, .entry.weighted { grid-template-columns: 1fr; } }
kbd { font-size: .72rem; padding: .05rem .35rem; border-radius: 4px; background: var(--surface-2); color: var(--ink); border: 1px solid var(--line); }
</style>
