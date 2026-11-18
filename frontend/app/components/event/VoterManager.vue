<template>
    <div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3"><StatCard label="Codes" :value="formatNumber(voters.length)" icon="bi-key" /></div>
            <div class="col-6 col-md-3"><StatCard label="Invited" :value="formatNumber(invitedCount)" icon="bi-send" tone="violet" /></div>
            <div class="col-6 col-md-3"><StatCard label="Voted" :value="formatNumber(usedCount)" icon="bi-check2-square" tone="green" /></div>
            <div class="col-6 col-md-3"><StatCard label="Not voted" :value="formatNumber(voters.length - usedCount)" icon="bi-hourglass-split" tone="amber" /></div>
        </div>

        <div class="card-b overflow-hidden">
            <div class="card-header-b">
                <div class="d-flex flex-column flex-sm-row gap-2 flex-grow-1">
                    <div class="input-icon flex-grow-1" style="max-width: 340px">
                        <i class="bi bi-search"></i>
                        <input v-model="query" type="search" class="form-control form-control-sm py-2" placeholder="Search name, email, phone or code" aria-label="Search voters" />
                    </div>
                    <select v-model="statusFilter" class="form-select form-select-sm py-2" style="max-width: 170px" aria-label="Filter by status">
                        <option value="all">All voters</option>
                        <option value="pending">Not voted</option>
                        <option value="voted">Voted</option>
                        <option value="not_invited">Not invited</option>
                        <option value="failed">Delivery failed</option>
                    </select>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-sm" @click="onSendCodes">
                        <i class="bi bi-send"></i> Send codes
                    </button>
                    <button class="btn btn-soft btn-sm" @click="dialog = 'import'"><i class="bi bi-upload"></i> Import</button>
                    <button class="btn btn-ghost btn-sm" @click="openForm(null)"><i class="bi bi-person-plus"></i> Add</button>
                    <button class="btn btn-ghost btn-sm" @click="dialog = 'generate'"><i class="bi bi-magic"></i> Generate</button>
                    <button class="btn btn-ghost btn-sm" :disabled="!voters.length" @click="exportCsv(voters)"><i class="bi bi-download"></i> CSV</button>
                    <button class="btn btn-ghost btn-sm" :disabled="!pendingVoters.length" @click="printSlips(pendingVoters)"><i class="bi bi-printer"></i> Print</button>
                </div>
            </div>

            <div v-if="!loading && voters.length && !reachableCount && event.status !== 'completed'" class="hint">
                <i class="bi bi-lightbulb"></i>
                <span class="flex-grow-1">
                    To send codes by email or SMS, voters need an email address or phone number.
                    Click <i class="bi bi-pencil"></i> on a voter to add one, or <b>Import</b> a list with contacts.
                </span>
                <button class="btn btn-soft btn-sm flex-shrink-0" @click="dialog = 'import'"><i class="bi bi-upload"></i> Import</button>
            </div>

            <div v-if="loading" class="p-4 d-grid gap-2">
                <div v-for="i in 5" :key="i" class="skeleton" style="height: 38px"></div>
            </div>
            <EmptyState v-else-if="!voters.length" icon="bi-people" title="No voters yet"
                text="Import your voter list with emails and phone numbers to send codes automatically, or generate anonymous codes to hand out.">
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <button class="btn btn-primary" @click="dialog = 'import'"><i class="bi bi-upload"></i> Import voters</button>
                    <button class="btn btn-soft" @click="dialog = 'generate'"><i class="bi bi-magic"></i> Generate codes</button>
                </div>
            </EmptyState>
            <EmptyState v-else-if="!filtered.length" icon="bi-search" title="No matching voters" />
            <template v-else>
                <div class="table-responsive">
                    <table class="table-b">
                        <thead>
                            <tr>
                                <th>Voter</th>
                                <th>
                                    <span class="d-inline-flex align-items-center gap-1">
                                        Code
                                        <button class="btn btn-link btn-sm p-0 text-muted lh-1" :title="revealAll ? 'Hide all codes' : 'Show all codes'"
                                            :aria-label="revealAll ? 'Hide all codes' : 'Show all codes'" @click="toggleAll">
                                            <i class="bi" :class="revealAll ? 'bi-eye-slash' : 'bi-eye'"></i>
                                        </button>
                                    </span>
                                </th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="v in paged" :key="v.id">
                                <td style="min-width: 200px">
                                    <div class="fw-semibold text-ink">{{ v.name || 'Anonymous' }}</div>
                                    <div v-if="v.email || v.phone" class="small text-muted d-flex flex-wrap column-gap-3">
                                        <span v-if="v.email" class="text-break"><i class="bi bi-envelope me-1"></i>{{ v.email }}</span>
                                        <span v-if="v.phone" class="text-nowrap"><i class="bi bi-phone me-1"></i>{{ v.phone }}</span>
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <span class="code-chip" :class="{ masked: !isRevealed(v.id) }">{{ isRevealed(v.id) ? v.code : maskCode(v.code) }}</span>
                                    <button class="btn btn-link btn-sm text-muted p-1" :title="isRevealed(v.id) ? 'Hide code' : 'Show code'"
                                        :aria-label="isRevealed(v.id) ? 'Hide code' : 'Show code'" @click="toggleOne(v.id)">
                                        <i class="bi" :class="isRevealed(v.id) ? 'bi-eye-slash' : 'bi-eye'"></i>
                                    </button>
                                    <button class="btn btn-link btn-sm text-muted p-1" title="Copy code" aria-label="Copy code" @click="copyText(v.code, 'Code copied')"><i class="bi bi-copy"></i></button>
                                </td>
                                <td class="text-nowrap">
                                    <span v-if="v.used_at" class="pill pill-active" :title="'Voted ' + formatDate(v.used_at, true)"><i class="bi bi-check2"></i> Voted</span>
                                    <span v-else-if="v.invite_error" class="pill pill-failed" :title="v.invite_error"><i class="bi bi-exclamation-triangle"></i> Not delivered</span>
                                    <span v-else-if="v.invited_at" class="pill pill-violet" :title="'Sent ' + formatDate(v.invited_at, true)"><i class="bi bi-send-check"></i> Invited</span>
                                    <span v-else class="pill pill-completed">Not invited</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button v-if="!v.used_at && (v.email || v.phone) && event.status !== 'completed'" class="btn btn-ghost btn-icon btn-sm"
                                        :title="v.invited_at ? 'Resend code' : 'Send code'" :aria-label="v.invited_at ? 'Resend code' : 'Send code'" @click="openInvite(v)">
                                        <i class="bi bi-send"></i>
                                    </button>
                                    <button class="btn btn-ghost btn-icon btn-sm ms-1" title="Edit" aria-label="Edit voter" @click="openForm(v)"><i class="bi bi-pencil"></i></button>
                                    <button v-if="v.used_at" class="btn btn-ghost btn-icon btn-sm ms-1" title="Let this code vote again" aria-label="Reset code" @click="resetVoter(v)">
                                        <i class="bi bi-arrow-counterclockwise"></i>
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

        <!-- Dialogs -->
        <EventVoterFormModal :open="dialog === 'form'" :event-id="event.id" :voter="editing" @close="dialog = null" @saved="onSaved" />
        <EventVoterImportModal :open="dialog === 'import'" :event-id="event.id" @close="dialog = null" @created="afterCreate" />
        <EventInviteModal :open="dialog === 'invite'" :event-id="event.id" :voters="voters" :single="inviting"
            @close="dialog = null" @sent="afterInvite" />

        <BaseModal :open="dialog === 'generate'" title="Generate anonymous codes" subtitle="For handing out on paper. To email or text codes, use Import." size="sm" @close="dialog = null">
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

        <BaseModal :open="dialog === 'created'" :title="`${created.length} code${created.length === 1 ? '' : 's'} created`"
            :subtitle="createdReachable ? 'Send them now, or download / print them.' : 'Download or print them to hand out.'" size="lg" @close="dialog = null">
            <div class="codes-grid">
                <div v-for="c in created.slice(0, 60)" :key="c.code" class="code-tile">
                    <div class="small text-muted truncate">{{ c.name || 'Anonymous' }}</div>
                    <div class="mono fw-bold text-brand">{{ c.code }}</div>
                </div>
            </div>
            <p v-if="created.length > 60" class="small text-muted mt-2 mb-0">…and {{ created.length - 60 }} more in the download.</p>
            <template #footer>
                <button class="btn btn-ghost" @click="exportCsv(created)"><i class="bi bi-download"></i> CSV</button>
                <button class="btn btn-ghost" @click="printSlips(created)"><i class="bi bi-printer"></i> Print slips</button>
                <button v-if="createdReachable && event.status !== 'completed'" class="btn btn-primary" @click="openInvite(null)">
                    <i class="bi bi-send"></i> Send codes now
                </button>
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
const statusFilter = ref<'all' | 'voted' | 'pending' | 'not_invited' | 'failed'>('all');
const page = ref(1);

const dialog = ref<null | 'form' | 'import' | 'generate' | 'created' | 'invite'>(null);
const editing = ref<Voter | null>(null);
const inviting = ref<Voter | null>(null);
const genCount = ref(50);
const created = ref<GeneratedCode[]>([]);

// --- Code privacy: hidden by default, revealed per row or all at once. ------
const revealAll = ref(false);
const revealed = ref(new Set<number>());
const isRevealed = (id: number) => revealAll.value !== revealed.value.has(id);
function toggleOne(id: number) {
    const next = new Set(revealed.value);
    if (next.has(id)) next.delete(id); else next.add(id);
    revealed.value = next;
}
function toggleAll() {
    revealAll.value = !revealAll.value;
    revealed.value = new Set();
}

// --- Derived lists -----------------------------------------------------------
const usedCount = computed(() => voters.value.filter(v => v.used_at).length);
const invitedCount = computed(() => voters.value.filter(v => v.invited_at).length);
const pendingVoters = computed(() => voters.value.filter(v => !v.used_at));
const reachableCount = computed(() => pendingVoters.value.filter(v => v.email || v.phone).length);
const createdReachable = computed(() => created.value.some(c => c.email || c.phone));

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    return voters.value.filter((v) => {
        const s = statusFilter.value;
        if (s === 'voted' && !v.used_at) return false;
        if (s === 'pending' && v.used_at) return false;
        if (s === 'not_invited' && (v.invited_at || v.used_at)) return false;
        if (s === 'failed' && !v.invite_error) return false;
        if (!q) return true;
        return [v.code, v.name, v.email, v.phone].some(f => (f ?? '').toLowerCase().includes(q));
    });
});
const pages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PER_PAGE)));
const paged = computed(() => filtered.value.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE));
watch([query, statusFilter], () => { page.value = 1; });
watch(pages, (p) => { if (page.value > p) page.value = p; });

// --- Data --------------------------------------------------------------------
async function load() {
    try {
        voters.value = (await adminApi.getVoters(props.event.id)).data;
    } catch (e) {
        toast.error(apiErrorMessage(e, 'Could not load voters.'));
    } finally {
        loading.value = false;
    }
}
onMounted(load);

// Invitations are delivered in the background: refresh statuses for a short while after sending.
let pollTimer: ReturnType<typeof setInterval> | undefined;
function pollStatuses() {
    clearInterval(pollTimer);
    let ticks = 0;
    pollTimer = setInterval(() => {
        if (++ticks > 12) { clearInterval(pollTimer); return; }
        load();
    }, 5000);
}
onBeforeUnmount(() => clearInterval(pollTimer));

function openForm(v: Voter | null) { editing.value = v; dialog.value = 'form'; }
function openInvite(v: Voter | null) { inviting.value = v; dialog.value = 'invite'; }

/** Always clickable, so the organiser learns why sending isn't possible yet. */
function onSendCodes() {
    if (props.event.status === 'completed') {
        toast.info('Voting has closed for this event, so codes can no longer be sent.');
    } else if (!voters.value.length) {
        toast.info('Add voters first. Import a list with emails or phone numbers.');
    } else if (!pendingVoters.value.length) {
        toast.info('Everyone has already voted. There is nobody left to send codes to.');
    } else if (!reachableCount.value) {
        toast.info('None of your voters has an email or phone number yet. Edit a voter or import a list with contacts.');
    } else {
        openInvite(null);
    }
}

async function onSaved(_v: Voter, isNew: boolean) {
    dialog.value = null;
    toast.success(isNew ? 'Voter added' : 'Voter updated');
    await load();
    emit('changed');
}

async function afterCreate(codes: GeneratedCode[]) {
    created.value = codes;
    dialog.value = 'created';
    await load();
    emit('changed');
}

async function afterInvite() {
    dialog.value = null;
    await load();
    pollStatuses();
}

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

async function resetVoter(v: Voter) {
    const ok = await confirm({
        title: 'Let this voter vote again?',
        message: `The ballot already cast by ${v.name || 'this code'} will be discarded, and the code can be used once more.`,
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
        title: `Delete ${v.name || 'this voter'}?`,
        message: v.used_at ? 'This voter has voted. Their ballot will be deleted too.' : 'Their code will stop working.',
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
    const rows: (string | null | undefined)[][] = [['Name', 'Email', 'Phone', 'Code', 'Voting link', 'Status', 'Invited']];
    for (const v of list) {
        const voter = 'id' in v ? v : null;
        rows.push([
            v.name ?? '', v.email ?? '', v.phone ?? '', v.code, voteUrl.value,
            voter?.used_at ? 'Voted' : 'Not voted',
            voter?.invited_at ? formatDate(voter.invited_at, true) : '',
        ]);
    }
    downloadFile(`${slugify(props.event.name)}-voters.csv`, toCsv(rows));
}

function printSlips(list: (Voter | GeneratedCode)[]) {
    printVoterSlips(props.event.name, voteUrl.value, list.map(v => ({ name: v.name, code: v.code })));
}
</script>

<style scoped>
.codes-grid { display: grid; gap: .5rem; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
.code-tile { padding: .6rem .75rem; border: 1px solid var(--line); border-radius: 12px; background: var(--surface-2); }
.code-chip.masked { color: var(--muted); letter-spacing: .12em; }
.hint {
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; padding: .8rem 1.25rem;
    background: var(--brand-50); color: var(--brand-900); font-size: .9rem; border-bottom: 1px solid var(--line);
}
.hint > .bi { color: var(--brand-600); font-size: 1.1rem; }
.pill-failed { background: var(--danger-50); color: var(--danger); cursor: help; }
</style>
