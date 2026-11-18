<template>
    <div>
        <div class="row g-3 mb-4">
            <div class="col-4"><StatCard label="Codes" :value="formatNumber(voters.length)" icon="bi-key" /></div>
            <div class="col-4"><StatCard label="Voted" :value="formatNumber(usedCount)" icon="bi-check2-square" tone="green" /></div>
            <div class="col-4"><StatCard label="Pending" :value="formatNumber(voters.length - usedCount)" icon="bi-hourglass-split" tone="amber" /></div>
        </div>

        <div class="card-b overflow-hidden">
            <div class="card-header-b">
                <div class="d-flex flex-column flex-sm-row gap-2 flex-grow-1">
                    <div class="input-icon flex-grow-1" style="max-width: 340px">
                        <i class="bi bi-search"></i>
                        <input v-model="query" type="search" class="form-control form-control-sm py-2" placeholder="Search name or code" aria-label="Search voters" />
                    </div>
                    <select v-model="statusFilter" class="form-select form-select-sm py-2" style="max-width: 160px" aria-label="Filter by status">
                        <option value="all">All voters</option>
                        <option value="voted">Voted</option>
                        <option value="pending">Not voted</option>
                    </select>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-sm" @click="openGenerate"><i class="bi bi-magic"></i> Generate</button>
                    <button class="btn btn-soft btn-sm" @click="openImport"><i class="bi bi-upload"></i> Import names</button>
                    <button class="btn btn-ghost btn-sm" @click="openAdd"><i class="bi bi-plus-lg"></i> Add one</button>
                    <button class="btn btn-ghost btn-sm" :disabled="!voters.length" @click="exportCsv(voters)"><i class="bi bi-download"></i> CSV</button>
                    <button class="btn btn-ghost btn-sm" :disabled="!pendingVoters.length" @click="printSlips(pendingVoters)"><i class="bi bi-printer"></i> Print</button>
                </div>
            </div>

            <div v-if="loading" class="p-4 d-grid gap-2">
                <div v-for="i in 5" :key="i" class="skeleton" style="height: 38px"></div>
            </div>
            <EmptyState v-else-if="!voters.length" icon="bi-key" title="No voter codes yet"
                text="Generate anonymous codes, or import a list of names to get one code per person.">
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <button class="btn btn-primary" @click="openGenerate"><i class="bi bi-magic"></i> Generate codes</button>
                    <button class="btn btn-soft" @click="openImport"><i class="bi bi-upload"></i> Import names</button>
                </div>
            </EmptyState>
            <EmptyState v-else-if="!filtered.length" icon="bi-search" title="No matching voters" />
            <template v-else>
                <div class="table-responsive">
                    <table class="table-b">
                        <thead>
                            <tr><th>Voter</th><th>Code</th><th>Status</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="v in paged" :key="v.id">
                                <td class="text-nowrap">
                                    <span class="fw-semibold text-ink">{{ v.name || 'Anonymous' }}</span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="code-chip">{{ v.code }}</span>
                                    <button class="btn btn-link btn-sm text-muted p-1" title="Copy code" aria-label="Copy code" @click="copyText(v.code, 'Code copied')"><i class="bi bi-copy"></i></button>
                                </td>
                                <td class="text-nowrap">
                                    <span v-if="v.used_at" class="pill pill-active" :title="formatDate(v.used_at, true)"><i class="bi bi-check2"></i> Voted</span>
                                    <span v-else class="pill pill-completed">Not voted</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button v-if="v.used_at" class="btn btn-ghost btn-sm" title="Allow this code to vote again" @click="resetVoter(v)">
                                        <i class="bi bi-arrow-counterclockwise"></i> <span class="d-none d-md-inline">Reset</span>
                                    </button>
                                    <button class="btn btn-ghost btn-icon btn-sm text-danger ms-1" title="Delete" aria-label="Delete voter" @click="removeVoter(v)"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="pages > 1" class="d-flex justify-content-between align-items-center px-3 py-2 border-top small">
                    <span class="text-muted">{{ (page - 1) * PER_PAGE + 1 }}–{{ Math.min(page * PER_PAGE, filtered.length) }} of {{ formatNumber(filtered.length) }}</span>
                    <div class="d-flex gap-1">
                        <button class="btn btn-ghost btn-icon btn-sm" :disabled="page === 1" aria-label="Previous page" @click="page--"><i class="bi bi-chevron-left"></i></button>
                        <button class="btn btn-ghost btn-icon btn-sm" :disabled="page === pages" aria-label="Next page" @click="page++"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Generate -->
        <BaseModal :open="dialog === 'generate'" title="Generate voter codes" subtitle="Anonymous one-time codes. Hand one to each voter." size="sm" @close="dialog = null">
            <form id="gen-form" @submit.prevent="generate">
                <label for="gen-count" class="form-label">How many codes?</label>
                <input id="gen-count" v-model.number="genCount" type="number" min="1" max="10000" class="form-control form-control-lg" />
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <button v-for="n in [25, 50, 100, 500]" :key="n" type="button" class="btn btn-ghost btn-sm" @click="genCount = n">{{ n }}</button>
                </div>
            </form>
            <template #footer>
                <button class="btn btn-ghost" @click="dialog = null">Cancel</button>
                <button type="submit" form="gen-form" class="btn btn-primary" :disabled="busy || !(genCount >= 1 && genCount <= 10000)">
                    <span v-if="busy" class="spinner-border spinner-border-sm"></span> Generate
                </button>
            </template>
        </BaseModal>

        <!-- Import -->
        <BaseModal :open="dialog === 'import'" title="Import voters" subtitle="One name per line. Each person gets their own code." @close="dialog = null">
            <form id="imp-form" @submit.prevent="importNames">
                <textarea v-model="importText" class="form-control mono" rows="8" placeholder="Ada Obi&#10;Bola Ade&#10;Chidi Eze"></textarea>
                <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                    <label class="btn btn-ghost btn-sm mb-0">
                        <i class="bi bi-filetype-csv"></i> Load from .csv / .txt
                        <input type="file" accept=".csv,.txt,text/csv,text/plain" class="visually-hidden" @change="loadFile" />
                    </label>
                    <span class="small text-muted">{{ importNamesList.length }} name{{ importNamesList.length === 1 ? '' : 's' }}</span>
                </div>
                <div class="form-text">From a CSV, the first column of each row is used.</div>
            </form>
            <template #footer>
                <button class="btn btn-ghost" @click="dialog = null">Cancel</button>
                <button type="submit" form="imp-form" class="btn btn-primary" :disabled="busy || !importNamesList.length || importNamesList.length > 10000">
                    <span v-if="busy" class="spinner-border spinner-border-sm"></span> Create {{ importNamesList.length || '' }} codes
                </button>
            </template>
        </BaseModal>

        <!-- Add one -->
        <BaseModal :open="dialog === 'add'" title="Add a voter" size="sm" @close="dialog = null">
            <form id="add-form" @submit.prevent="addOne">
                <div class="mb-3">
                    <label for="add-name" class="form-label">Name <span class="text-muted fw-normal">(optional)</span></label>
                    <input id="add-name" v-model.trim="addForm.name" class="form-control" maxlength="255" />
                </div>
                <label for="add-code" class="form-label">Code <span class="text-muted fw-normal">(optional)</span></label>
                <input id="add-code" v-model.trim="addForm.code" class="form-control mono text-uppercase" maxlength="64" placeholder="Leave empty to generate" />
                <div v-if="addError" class="field-error">{{ addError }}</div>
            </form>
            <template #footer>
                <button class="btn btn-ghost" @click="dialog = null">Cancel</button>
                <button type="submit" form="add-form" class="btn btn-primary" :disabled="busy">Add voter</button>
            </template>
        </BaseModal>

        <!-- Fresh codes -->
        <BaseModal :open="dialog === 'created'" :title="`${created.length} code${created.length === 1 ? '' : 's'} ready`"
            subtitle="Download or print them now to hand out." size="lg" @close="dialog = null">
            <div class="codes-grid">
                <div v-for="c in created.slice(0, 60)" :key="c.code" class="code-tile">
                    <div class="small text-muted truncate">{{ c.name || 'Anonymous' }}</div>
                    <div class="mono fw-bold text-brand">{{ c.code }}</div>
                </div>
            </div>
            <p v-if="created.length > 60" class="small text-muted mt-2 mb-0">…and {{ created.length - 60 }} more in the download.</p>
            <template #footer>
                <button class="btn btn-ghost" @click="exportCsv(created)"><i class="bi bi-download"></i> Download CSV</button>
                <button class="btn btn-primary" @click="printSlips(created)"><i class="bi bi-printer"></i> Print slips</button>
            </template>
        </BaseModal>
    </div>
</template>

<script setup lang="ts">
import { adminApi } from '~/api/endpoints/admin';
import type { BallotEvent, GeneratedCode, Voter } from '~/types';

const props = defineProps<{ event: BallotEvent }>();
const emit = defineEmits<{ changed: [] }>();

const PER_PAGE = 50;
const toast = useToast();
const { confirm } = useConfirm();
const { voteUrl } = useEventLinks(() => props.event.link_token);

const loading = ref(true);
const busy = ref(false);
const voters = ref<Voter[]>([]);
const query = ref('');
const statusFilter = ref<'all' | 'voted' | 'pending'>('all');
const page = ref(1);

const dialog = ref<null | 'generate' | 'import' | 'add' | 'created'>(null);
const genCount = ref(50);
const importText = ref('');
const addForm = reactive({ name: '', code: '' });
const addError = ref('');
const created = ref<GeneratedCode[]>([]);

const usedCount = computed(() => voters.value.filter(v => v.used_at).length);
const pendingVoters = computed(() => voters.value.filter(v => !v.used_at));
const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    return voters.value.filter(v =>
        (statusFilter.value === 'all' || (statusFilter.value === 'voted' ? !!v.used_at : !v.used_at))
        && (!q || v.code.toLowerCase().includes(q) || (v.name ?? '').toLowerCase().includes(q)));
});
const pages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PER_PAGE)));
const paged = computed(() => filtered.value.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE));
watch([query, statusFilter], () => { page.value = 1; });
watch(pages, (p) => { if (page.value > p) page.value = p; });

const importNamesList = computed(() =>
    importText.value.split(/\r?\n/).map(l => l.trim()).filter(Boolean));

async function load() {
    loading.value = true;
    try {
        voters.value = (await adminApi.getVoters(props.event.id)).data;
    } catch (e) {
        toast.error(apiErrorMessage(e, 'Could not load voters.'));
    } finally {
        loading.value = false;
    }
}
onMounted(load);

async function afterCreate(codes: GeneratedCode[]) {
    created.value = codes;
    dialog.value = 'created';
    await load();
    emit('changed');
}

function openGenerate() { dialog.value = 'generate'; }
function openImport() { importText.value = ''; dialog.value = 'import'; }
function openAdd() { addForm.name = ''; addForm.code = ''; addError.value = ''; dialog.value = 'add'; }

async function generate() {
    busy.value = true;
    try {
        const { data } = await adminApi.generateVoters(props.event.id, { count: genCount.value });
        await afterCreate(data.voters);
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        busy.value = false;
    }
}

async function importNames() {
    busy.value = true;
    try {
        const { data } = await adminApi.importVoters(props.event.id, { names: importNamesList.value });
        await afterCreate(data.voters);
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        busy.value = false;
    }
}

async function loadFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) { toast.error('That file is larger than 2 MB.'); return; }
    const text = await file.text();
    const names = text.split(/\r?\n/)
        .map(line => (line.split(',')[0] ?? '').replace(/^﻿/, '').replace(/^"|"$/g, '').trim())
        .filter(Boolean);
    // Drop an obvious header row.
    if (names[0] && /^(name|names|full ?name|voter)$/i.test(names[0])) names.shift();
    importText.value = names.join('\n');
}

async function addOne() {
    busy.value = true;
    addError.value = '';
    try {
        if (addForm.code) {
            const { data } = await adminApi.createVoter(props.event.id, { name: addForm.name || null, code: addForm.code });
            await afterCreate([{ name: data.name, code: data.code }]);
        } else {
            const { data } = await adminApi.importVoters(props.event.id, { names: [addForm.name] });
            await afterCreate(data.voters);
        }
    } catch (e) {
        addError.value = apiErrorMessage(e);
    } finally {
        busy.value = false;
    }
}

async function resetVoter(v: Voter) {
    const ok = await confirm({
        title: 'Let this code vote again?',
        message: `The ballot already cast with ${v.code} will be discarded, and the code can be used once more.`,
        confirmText: 'Reset code',
        danger: true,
    });
    if (!ok) return;
    try {
        await adminApi.updateVoter(props.event.id, v.id, { used_at: null });
        toast.success('Code reset. It can vote again.');
        await load();
        emit('changed');
    } catch (e) {
        toast.error(apiErrorMessage(e));
    }
}

async function removeVoter(v: Voter) {
    const ok = await confirm({
        title: `Delete code ${v.code}?`,
        message: v.used_at ? 'This voter has voted. Their ballot will be deleted too.' : 'The code will stop working.',
        confirmText: 'Delete',
        danger: true,
    });
    if (!ok) return;
    try {
        await adminApi.deleteVoter(props.event.id, v.id);
        voters.value = voters.value.filter(x => x.id !== v.id);
        toast.success('Voter deleted');
        emit('changed');
    } catch (e) {
        toast.error(apiErrorMessage(e));
    }
}

function exportCsv(list: (Voter | GeneratedCode)[]) {
    const rows: (string | null)[][] = [['Name', 'Code', 'Voting link', 'Status']];
    for (const v of list) {
        const used = 'used_at' in v ? (v.used_at ? 'Voted' : 'Not voted') : 'Not voted';
        rows.push([v.name ?? '', v.code, voteUrl.value, used]);
    }
    downloadFile(`${slugify(props.event.name)}-voter-codes.csv`, toCsv(rows));
}

function printSlips(list: (Voter | GeneratedCode)[]) {
    printVoterSlips(props.event.name, voteUrl.value, list.map(v => ({ name: v.name, code: v.code })));
}
</script>

<style scoped>
.codes-grid { display: grid; gap: .5rem; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
.code-tile { padding: .6rem .75rem; border: 1px solid var(--line); border-radius: 12px; background: var(--surface-2); }
</style>
