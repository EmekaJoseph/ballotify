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
                    <AppSelect v-model="statusFilter" :options="STATUS_FILTERS" size="sm" :block="false" icon="bi-funnel" class="status-filter" aria-label="Filter by status" />
                    <AppSelect v-if="groupNames.length" v-model="groupFilter" :options="groupFilterOptions" size="sm" :block="false" icon="bi-people"
                        class="status-filter" aria-label="Filter by group" :menu-min-width="200" />
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-sm" @click="onSendCodes">
                        <i class="bi bi-send"></i> Send codes
                    </button>
                    <button class="btn btn-wa btn-sm" :disabled="!pendingVoters.length || event.status === 'completed'" title="Send each voter their code on WhatsApp (free)" @click="dialog = 'whatsapp'">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </button>
                    <button class="btn btn-soft btn-sm" :disabled="event.status !== 'active' && event.status !== 'upcoming'" title="Remind people who haven’t voted" @click="onRemind">
                        <i class="bi bi-alarm"></i> Remind
                    </button>
                    <button class="btn btn-soft btn-sm" @click="dialog = 'import'"><i class="bi bi-upload"></i> Import</button>
                    <button class="btn btn-ghost btn-sm" @click="openForm(null)"><i class="bi bi-person-plus"></i> Add</button>
                    <button class="btn btn-ghost btn-sm" @click="dialog = 'generate'"><i class="bi bi-magic"></i> Generate</button>
                    <button class="btn btn-ghost btn-sm" :disabled="!voters.length" @click="exportCsv(voters)"><i class="bi bi-download"></i> CSV</button>
                    <button class="btn btn-ghost btn-sm" :disabled="!pendingVoters.length" @click="printSlips(pendingVoters)"><i class="bi bi-printer"></i> Print</button>
                </div>
            </div>

            <div v-if="event.reminder_at && !event.reminder_sent_at" class="hint">
                <i class="bi bi-alarm"></i>
                <span>Reminder scheduled for <b>{{ formatDate(event.reminder_at, true) }}</b> to everyone who hasn’t voted by then.</span>
                <button class="btn btn-soft btn-sm flex-shrink-0" @click="dialog = 'remind'">Change</button>
            </div>

            <div v-if="event.weighted_votes || event.proxies_enabled" class="hint">
                <i class="bi bi-bank"></i>
                <span class="flex-grow-1">
                    <template v-if="event.weighted_votes">
                        <b>Weighted votes are on:</b> {{ formatNumber(totalWeight) }} votes in total across {{ formatNumber(voters.length) }} members.
                        Set weights with <i class="bi bi-pencil"></i> or a <b>Weight</b> column when you import.
                    </template>
                    <template v-if="event.proxies_enabled">
                        <b>Proxy voting is on:</b> click <i class="bi bi-pencil"></i> on a member to choose who votes for them
                        (up to {{ event.max_proxies ?? 2 }} per holder).
                        <template v-if="proxyCount"> {{ formatNumber(proxyCount) }} prox{{ proxyCount === 1 ? 'y' : 'ies' }} assigned.</template>
                    </template>
                </span>
            </div>

            <div v-if="isFree" class="hint plan-hint">
                <i class="bi bi-box"></i>
                <span class="flex-grow-1">
                    <b>Free event:</b> {{ formatNumber(voters.length) }} of {{ formatNumber(event.limits!.max_voters ?? 0) }} voters used.
                    Share codes free on WhatsApp, by CSV or printed slips. Upgrade to Pro ({{ fromPriceLabel }}) for more voters, email/SMS invitations and reminders.
                </span>
                <button v-if="event.status !== 'completed'" class="btn btn-soft btn-sm flex-shrink-0" @click="askUpgrade('Upgrade to Pro')">
                    <i class="bi bi-lightning-charge"></i> Upgrade
                </button>
            </div>

            <div v-if="!loading && voters.length && !reachableCount && event.status !== 'completed' && !isFree" class="hint">
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
                <!-- Selection bar -->
                <div v-if="selected.size" class="select-bar">
                    <span class="fw-bold">{{ selected.size }} selected</span>
                    <button class="btn btn-primary btn-sm" :disabled="event.status === 'completed'" @click="sendToSelected">
                        <i class="bi bi-send"></i> Send codes to selected
                    </button>
                    <button class="btn btn-link btn-sm text-muted ms-auto" @click="clearSelection">Clear selection</button>
                </div>
                <div class="table-responsive">
                    <table class="table-b">
                        <thead>
                            <tr>
                                <th class="pe-0" style="width: 36px">
                                    <input class="form-check-input" type="checkbox" :checked="pageAllSelected" :indeterminate="pageSomeSelected"
                                        aria-label="Select all on this page" @change="togglePage" />
                                </th>
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
                            <tr v-for="v in paged" :key="v.id" :class="{ 'row-selected': selected.has(v.id) }">
                                <td class="pe-0">
                                    <input class="form-check-input" type="checkbox" :checked="selected.has(v.id)"
                                        :aria-label="`Select ${v.name || 'voter'}`" @change="toggleSelect(v.id)" />
                                </td>
                                <td style="min-width: 200px">
                                    <div class="fw-semibold text-ink">{{ v.name || 'Anonymous' }}</div>
                                    <div v-if="v.groups?.length || (event.weighted_votes && (v.weight ?? 1) !== 1) || proxyLabel(v) || heldBy.get(v.id)" class="d-flex flex-wrap gap-1 my-1">
                                        <span v-for="g in v.groups" :key="g" class="group-chip">{{ g }}</span>
                                        <span v-if="event.weighted_votes && (v.weight ?? 1) !== 1" class="rule-chip" title="Weight: this ballot counts as this many votes">
                                            <i class="bi bi-bar-chart-steps"></i> {{ formatNumber(v.weight ?? 1) }} votes
                                        </span>
                                        <span v-if="proxyLabel(v)" class="rule-chip proxy text-truncate" :title="proxyLabel(v)">
                                            <i class="bi bi-arrow-right-circle"></i> {{ proxyLabel(v) }}
                                        </span>
                                        <span v-if="heldBy.get(v.id)" class="rule-chip proxy" :title="`Votes for: ${heldBy.get(v.id)!.join(', ')}`">
                                            <i class="bi bi-person-check"></i> Holds {{ heldBy.get(v.id)!.length }} prox{{ heldBy.get(v.id)!.length === 1 ? 'y' : 'ies' }}
                                        </span>
                                    </div>
                                    <div v-if="v.email || v.phone || v.member_id" class="small text-muted d-flex flex-wrap column-gap-3">
                                        <span v-if="v.email" class="text-break"><i class="bi bi-envelope me-1"></i>{{ v.email }}</span>
                                        <span v-if="v.phone" class="text-nowrap"><i class="bi bi-phone me-1"></i>{{ v.phone }}</span>
                                        <span v-if="v.member_id" class="text-nowrap mono" :title="event.member_id_label || 'Member ID'"><i class="bi bi-person-vcard me-1"></i>{{ v.member_id }}</span>
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
                                    <span v-if="v.used_at && v.proxy_voter_id" class="pill pill-active" :title="'Voted by proxy ' + formatDate(v.used_at, true)"><i class="bi bi-check2"></i> Voted by proxy</span>
                                    <span v-else-if="v.used_at" class="pill pill-active" :title="'Voted ' + formatDate(v.used_at, true)"><i class="bi bi-check2"></i> Voted</span>
                                    <span v-else-if="v.proxy_voter_id" class="pill pill-violet" title="Their proxy votes for them; their own code doesn’t work"><i class="bi bi-arrow-right-circle"></i> Proxy given</span>
                                    <span v-else-if="v.invite_error" class="pill pill-failed" :title="v.invite_error"><i class="bi bi-exclamation-triangle"></i> Not delivered</span>
                                    <span v-else-if="v.invited_at && v.invited_via === 'whatsapp'" class="pill pill-wa" :title="'Shared on WhatsApp ' + formatDate(v.invited_at, true)"><i class="bi bi-whatsapp"></i> Shared</span>
                                    <span v-else-if="v.invited_at" class="pill pill-violet" :title="'Sent ' + formatDate(v.invited_at, true)"><i class="bi bi-send-check"></i> Invited</span>
                                    <span v-else class="pill pill-completed">Not invited</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button v-if="!v.used_at && event.status !== 'completed'" class="btn btn-soft btn-sm"
                                        :title="!(v.email || v.phone) ? 'Add an email or phone number first' : (v.invited_at ? 'Resend this voter\'s code' : 'Send this voter their code')"
                                        @click="sendToOne(v)">
                                        <i class="bi bi-send"></i> {{ v.invited_at ? 'Resend' : 'Send' }}
                                    </button>
                                    <a v-if="!v.used_at && event.status !== 'completed'" :href="waLink(v)" target="_blank" rel="noopener" class="btn btn-ghost btn-icon btn-sm ms-1 wa-icon"
                                        title="Send on WhatsApp" aria-label="Send code on WhatsApp" @click="markShared(v)"><i class="bi bi-whatsapp"></i></a>
                                    <button class="btn btn-ghost btn-icon btn-sm ms-1" title="Edit" aria-label="Edit voter" @click="openForm(v)"><i class="bi bi-pencil"></i></button>
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
        <EventVoterFormModal :open="dialog === 'form'" :event-id="event.id" :voter="editing" :member-label="event.member_id_label || 'Member ID'" :group-options="groupNames" :rules="formRules" @close="dialog = null" @saved="onSaved" @limit="onLimit" />
        <EventVoterImportModal :open="dialog === 'import'" :event-id="event.id" :member-label="event.member_id_label || 'Member ID'" :weighted="!!event.weighted_votes" @close="dialog = null" @created="afterCreate" @limit="onLimit" />
        <EventWhatsAppModal :open="dialog === 'whatsapp'" :event="event" :voters="voters" :vote-url="voteUrl" @close="dialog = null" @shared="onShared" />
        <EventRemindModal :open="dialog === 'remind'" :event="event" :pending="pendingVoters.length" :reachable="reachableCount" :phone-pending="pendingVoters.filter(v => v.phone).length"
            @close="dialog = null" @done="afterRemind" />
        <EventInviteModal :open="dialog === 'invite'" :event-id="event.id" :voters="voters" :targets="inviting" :sms="event.limits?.sms"
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
/** Voter groups in use (for the filter and the voter form's suggestions). */
const groupNames = computed(() => {
    const seen = new Map<string, string>();
    for (const v of voters.value) for (const g of v.groups ?? []) if (!seen.has(g.toLowerCase())) seen.set(g.toLowerCase(), g);
    return [...seen.values()].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
});
const groupFilter = ref('');
const groupFilterOptions = computed(() => [{ value: '', label: 'All groups' }, ...groupNames.value.map(g => ({ value: g, label: g }))]);
const STATUS_FILTERS: { value: typeof statusFilter.value; label: string }[] = [
    { value: 'all', label: 'All voters' },
    { value: 'pending', label: 'Not voted' },
    { value: 'voted', label: 'Voted' },
    { value: 'not_invited', label: 'Not invited' },
    { value: 'failed', label: 'Delivery failed' },
];
const page = ref(1);

const dialog = ref<null | 'form' | 'import' | 'generate' | 'created' | 'invite' | 'whatsapp' | 'remind'>(null);
const editing = ref<Voter | null>(null);
/** null = whole list (pick an audience); array = exactly these voters. */
const inviting = ref<Voter[] | null>(null);

// --- Row selection ---------------------------------------------------------------
const selected = ref(new Set<number>());
const pageAllSelected = computed(() => paged.value.length > 0 && paged.value.every(v => selected.value.has(v.id)));
const pageSomeSelected = computed(() => !pageAllSelected.value && paged.value.some(v => selected.value.has(v.id)));
function toggleSelect(id: number) {
    const next = new Set(selected.value);
    if (next.has(id)) next.delete(id); else next.add(id);
    selected.value = next;
}
function togglePage() {
    const next = new Set(selected.value);
    const all = pageAllSelected.value;
    for (const v of paged.value) { if (all) next.delete(v.id); else next.add(v.id); }
    selected.value = next;
}
function clearSelection() { selected.value = new Set(); }
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
        if (groupFilter.value && !(v.groups ?? []).some(g => g.toLowerCase() === groupFilter.value.toLowerCase())) return false;
        if (!q) return true;
        return [v.code, v.name, v.email, v.phone, v.member_id].some(f => (f ?? '').toLowerCase().includes(q));
    });
});
const pages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PER_PAGE)));
const paged = computed(() => filtered.value.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE));
watch([query, statusFilter, groupFilter], () => { page.value = 1; });
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
function openInvite(targets: Voter[] | null) {
    if (isFree.value) {
        askUpgrade('Sending codes is a Pro feature');
        return;
    }
    inviting.value = targets;
    dialog.value = 'invite';
}

// ----- Plan (Free / Pro) -----
const { plans, loadPlans, fromPriceLabel, openUpgrade } = useBilling();
onMounted(() => loadPlans());
const isFree = computed(() => props.event.limits?.plan === 'free' && props.event.limits.max_voters !== null);

/** Out of room (or messaging needs Pro): choose a Pro size. */
async function askUpgrade(_title: string) {
    await loadPlans();
    openUpgrade({ id: props.event.id, name: props.event.name, limits: props.event.limits, voters: voters.value.length });
}

/** Row "Send": if the voter can't be reached yet, take the organiser to add a contact. */
function sendToOne(v: Voter) {
    if (!v.email && !v.phone) {
        toast.info(`Add an email or phone number for ${v.name || 'this voter'} to send their code.`);
        openForm(v);
        return;
    }
    openInvite([v]);
}

function sendToSelected() {
    const list = voters.value.filter(v => selected.value.has(v.id));
    if (!list.some(v => !v.used_at && (v.email || v.phone))) {
        toast.info('None of the selected voters can be messaged: they have already voted or have no email or phone number.');
        return;
    }
    openInvite(list);
}

/** Always clickable, so the organiser learns why sending isn't possible yet. */
function onSendCodes() {
    if (isFree.value && props.event.status !== 'completed') {
        askUpgrade('Sending codes is a Pro feature');
    } else if (props.event.status === 'completed') {
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

// ------------------------------------------------------------------ meeting rules
const totalWeight = computed(() => voters.value.reduce((n, v) => n + (v.weight ?? 1), 0));
const proxyCount = computed(() => voters.value.filter(v => v.proxy_voter_id).length);
/** Holder id => names of the members they vote for. */
const heldBy = computed(() => {
    const map = new Map<number, string[]>();
    if (!props.event.proxies_enabled) return map;
    for (const v of voters.value) {
        if (v.proxy_voter_id) map.set(v.proxy_voter_id, [...(map.get(v.proxy_voter_id) ?? []), v.name || 'a member']);
    }
    return map;
});
function proxyLabel(v: Voter): string {
    if (!v.proxy_voter_id) return '';
    const holder = voters.value.find(x => x.id === v.proxy_voter_id);
    return `Proxy: ${holder?.name || 'a member'}`;
}
const formRules = computed(() => (props.event.weighted_votes || props.event.proxies_enabled
    ? { weighted: !!props.event.weighted_votes, proxies: !!props.event.proxies_enabled, maxProxies: props.event.max_proxies ?? 2, locked: props.event.ballot_locked, voters: voters.value }
    : null));

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
    if (inviting.value && inviting.value.length > 1) clearSelection();
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
        if (planLimitReason(e) === 'events') return onLimit();
        toast.error(apiErrorMessage(e));
    } finally {
        busy.value = false;
    }
}


/** Removed straight away, with a few seconds to undo (no confirm dialog). */
function removeVoter(v: Voter) {
    const index = voters.value.findIndex(x => x.id === v.id);
    const who = v.name ? `“${v.name}”` : 'voter';
    toast.undoable(`Deleted ${who}` + (v.used_at ? ' (their secret vote stays counted)' : ''), {
        hide: () => { voters.value = voters.value.filter(x => x.id !== v.id); },
        restore: () => {
            if (voters.value.some(x => x.id === v.id)) return;
            const next = [...voters.value];
            next.splice(Math.min(index, next.length), 0, v);
            voters.value = next;
        },
        commit: async () => {
            await adminApi.deleteVoter(props.event.id, v.id);
            emit('changed');
        },
    });
}

// ----- WhatsApp (free: opens the organiser's own WhatsApp with the message ready) -----
function waLink(v: Voter) {
    return whatsappLink(v.phone, voterCodeMessage({
        eventName: props.event.name, voterName: v.name, code: v.code, voteUrl: voteUrl.value,
        endsAt: props.event.ends_at, reminder: !!v.invited_at,
    }));
}
async function markShared(v: Voter) {
    try { onShared((await adminApi.markShared(props.event.id, v.id)).data); } catch { /* the message still opened */ }
}
function onShared(updated: Voter) {
    voters.value = voters.value.map(x => (x.id === updated.id ? { ...x, ...updated } : x));
}

// ----- Reminders (Pro) -----
function onRemind() {
    if (isFree.value) return askUpgrade('Reminders are a Pro feature');
    if (!pendingVoters.value.length) return toast.info('Everyone has voted. Nobody needs a reminder.');
    dialog.value = 'remind';
}
async function afterRemind() {
    dialog.value = null;
    emit('changed');
    await load();
    pollStatuses();
}

// ----- Free plan: adding voters here would be a second live event -----
const { showLimit } = useNewEvent();
function onLimit() {
    dialog.value = null;
    showLimit({ id: props.event.id, name: props.event.name });
}

function exportCsv(list: (Voter | GeneratedCode)[]) {
    const weighted = !!props.event.weighted_votes;
    const proxies = !!props.event.proxies_enabled;
    const rows: (string | null | undefined)[][] = [['Name', 'Email', 'Phone', 'Code', 'Voting link', 'Status', 'Invited',
        ...(weighted ? ['Weight'] : []), ...(proxies ? ['Proxy (votes for them)'] : [])]];
    for (const v of list) {
        const voter = 'id' in v ? v : null;
        rows.push([
            v.name ?? '', v.email ?? '', v.phone ?? '', v.code, voteUrl.value,
            voter?.used_at ? (voter.proxy_voter_id ? 'Voted by proxy' : 'Voted') : 'Not voted',
            voter?.invited_at ? formatDate(voter.invited_at, true) : '',
            ...(weighted ? [String(voter?.weight ?? 1)] : []),
            ...(proxies ? [voter ? proxyLabel(voter).replace(/^Proxy: /, '') : ''] : []),
        ]);
    }
    downloadFile(`${slugify(props.event.name)}-voters.csv`, toCsv(rows));
}

function printSlips(list: (Voter | GeneratedCode)[]) {
    printVoterSlips(props.event.name, voteUrl.value, list.map(v => ({ name: v.name, code: v.code })));
}
</script>

<style scoped>
.rule-chip { display: inline-flex; align-items: center; gap: .25rem; max-width: 220px; font-size: .7rem; font-weight: 700; padding: .05rem .45rem; border-radius: 6px; background: var(--pro-bg); color: var(--ink); box-shadow: inset 0 0 0 1px var(--pro-ring); }
.rule-chip .bi { color: var(--pro-icon); }
.rule-chip.proxy { background: var(--brand-50); box-shadow: none; }
.rule-chip.proxy .bi { color: var(--brand-600); }
.group-chip { font-size: .7rem; font-weight: 700; padding: .05rem .45rem; border-radius: 6px; background: var(--violet-50, rgba(124, 92, 255, .1)); color: var(--violet-600, #6d4ce8); }
.status-filter { flex-shrink: 0; min-width: 160px; }
.status-filter :deep(.as-trigger) { padding-top: .5rem; padding-bottom: .5rem; }
.codes-grid { display: grid; gap: .5rem; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
.code-tile { padding: .6rem .75rem; border: 1px solid var(--line); border-radius: 12px; background: var(--surface-2); }
.code-chip.masked { color: var(--muted); letter-spacing: .12em; }
.hint {
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; padding: .8rem 1.25rem;
    background: var(--brand-50); color: var(--brand-900); font-size: .9rem; border-bottom: 1px solid var(--line);
}
.hint > .bi { color: var(--brand-600); font-size: 1.1rem; }
/* Text takes the remaining width and wraps inside; the button only drops below on narrow screens. */
.hint > span { flex: 1 1 260px; min-width: 0; }
.select-bar {
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; padding: .6rem 1.25rem;
    background: var(--brand-50); color: var(--brand-900); border-bottom: 1px solid var(--brand-100); font-size: .9rem;
}
.row-selected td { background: rgba(20, 145, 138, .05); }
.pill-failed { background: var(--danger-50); color: var(--danger); cursor: help; }
.pill-wa { background: rgba(29, 168, 81, .12); color: #178a43; }
.wa-icon { color: #1da851; }
.btn-wa { --bs-btn-color: #fff; --bs-btn-bg: #1da851; --bs-btn-border-color: #1da851; --bs-btn-hover-color: #fff; --bs-btn-hover-bg: #178a43; --bs-btn-hover-border-color: #178a43; --bs-btn-disabled-bg: #1da851; --bs-btn-disabled-border-color: #1da851; --bs-btn-disabled-color: #fff; }
</style>
