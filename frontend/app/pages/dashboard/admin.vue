<template>
    <div>
        <PageHeader title="Platform admin" subtitle="Everyone using this Ballotify installation. Ballot contents, voter lists and codes are never shown here." />

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <StatCard label="Organisers" :value="ov ? formatNumber(ov.users) : '–'" icon="bi-person-badge"
                    :hint="ov ? `+${formatNumber(ov.users_last_30_days)} in 30 days` : ''" />
            </div>
            <div class="col-6 col-xl-3">
                <StatCard label="Events" :value="ov ? formatNumber(ov.events) : '–'" icon="bi-calendar2-check" tone="violet"
                    :hint="ov ? `${formatNumber(ov.events_live)} open · ${formatNumber(ov.pro_events)} Pro` : ''" />
            </div>
            <div class="col-6 col-xl-3">
                <StatCard label="Revenue" :value="ov ? formatNaira(ov.revenue_naira) : '–'" icon="bi-cash-coin" tone="green"
                    :hint="ov ? `${formatNaira(ov.revenue_last_30_days_naira)} in 30 days · ${formatNaira(ov.paid_votes_fees_naira)} vote fees` : ''" />
            </div>
            <div class="col-6 col-xl-3">
                <StatCard label="Ballots cast" :value="ov ? formatNumber(ov.ballots_cast) : '–'" icon="bi-check2-square" tone="amber"
                    :hint="ov ? `${formatNumber(ov.suspended_users)} suspended account${ov.suspended_users === 1 ? '' : 's'}` : ''" />
            </div>
        </div>

        <div class="segmented mb-3" role="tablist">
            <button v-for="t in tabs" :key="t.key" type="button" :class="{ active: tab === t.key }" @click="tab = t.key">
                <i class="bi" :class="t.icon"></i> {{ t.label }}
            </button>
        </div>

        <!-- Pricing -->
        <div v-if="tab === 'pricing'" class="row g-4">
            <div class="col-lg-7">
                <form class="card-b pad" @submit.prevent="savePricing">
                    <h3 class="h6 fw-800 mb-1">Plans and prices</h3>
                    <p class="small text-muted mb-4">Changes apply straight away to new limits and new checkouts. Events already upgraded stay Pro.</p>

                    <div class="small-caps mb-2">Pro (one-off, per event)</div>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label" for="p-price">Price (₦)</label>
                            <input id="p-price" v-model.number="pricing.pro_price_naira" type="number" min="100" step="100" class="form-control" required />
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="p-pmax">Voters per Pro event</label>
                            <input id="p-pmax" v-model.number="pricing.pro_max_voters" type="number" min="1" class="form-control" required />
                        </div>
                    </div>

                    <div class="small-caps mb-2">Free</div>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label" for="p-fmax">Voters per free event</label>
                            <input id="p-fmax" v-model.number="pricing.free_max_voters" type="number" min="1" class="form-control" required />
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="p-fev">Live free events per account</label>
                            <input id="p-fev" v-model.number="pricing.free_max_active_events" type="number" min="1" class="form-control" required />
                        </div>
                    </div>
                    <div class="small text-muted mb-4">Free events can’t send codes by email or SMS, and show “Powered by Ballotify” on the ballot. Drafts (events without voters) are unlimited.</div>

                    <div class="small-caps mb-2">Paid public voting</div>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label" for="p-fee">Platform fee (%)</label>
                            <input id="p-fee" v-model.number="pricing.paid_vote_fee_percent" type="number" min="0" max="50" class="form-control" required />
                        </div>
                        <div class="col-sm-6 small text-muted d-flex align-items-end">Your share of every vote payment. The rest is paid out to the organiser.</div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button class="btn btn-primary" :disabled="savingPricing">
                            <span v-if="savingPricing" class="spinner-border spinner-border-sm"></span> Save prices
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-lg-5 d-grid gap-4 align-content-start">
                <div class="card-b pad">
                    <h3 class="h6 fw-800 mb-3">Payments</h3>
                    <div v-if="pricingStatus" class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span v-for="p in providerPills" :key="p.key" class="pill" :class="p.on ? 'pill-active' : 'pill-upcoming'"><span class="dot"></span>
                            {{ p.label }} {{ p.on ? (p.backup ? 'connected (backup)' : 'connected') : 'not set up' }}
                        </span>
                    </div>
                    <p v-if="pricingStatus && !pricingStatus.payments_enabled" class="small text-muted mb-2">
                        Add <code>PAYSTACK_SECRET_KEY</code> (and, as a backup, <code>FLUTTERWAVE_SECRET_KEY</code>) to the API’s <code>.env</code>,
                        then run <code>php artisan config:clear</code>.
                        Until then organisers see “Contact us to upgrade”, and you can upgrade events yourself from the Events tab.
                    </p>
                    <p class="small text-muted mb-1">Paystack dashboard → webhook URL:</p>
                    <code class="d-block small text-break mb-2">{{ webhookUrl }}</code>
                    <p class="small text-muted mb-1">Flutterwave dashboard → Settings → Webhooks → URL (and the same secret hash as <code>FLUTTERWAVE_WEBHOOK_HASH</code>):</p>
                    <code class="d-block small text-break">{{ flutterwaveWebhookUrl }}</code>
                </div>
                <div class="card-b pad">
                    <h3 class="h6 fw-800 mb-2">Free upgrades</h3>
                    <p class="small text-muted mb-0">
                        <b>Events tab → Make Pro</b> upgrades one event without payment.
                        <b>Organisers tab → Give Pro</b> makes every event of that account Pro (for partners, sponsors or staff).
                    </p>
                </div>
            </div>
        </div>

        <AdminPayouts v-else-if="tab === 'payouts'" />
        <AdminHealth v-else-if="tab === 'health'" />

        <template v-else>
            <div class="d-flex flex-column flex-md-row gap-2 mb-3">
                <div class="input-icon flex-grow-1" style="max-width: 420px">
                    <i class="bi bi-search"></i>
                    <input v-model="search" type="search" class="form-control" :placeholder="searchPlaceholder" aria-label="Search" />
                </div>
                <div class="segmented">
                    <button v-for="f in filters" :key="f.value" type="button" :class="{ active: filter === f.value }" @click="filter = f.value">
                        {{ f.label }}
                    </button>
                </div>
            </div>

            <div class="card-b overflow-hidden">
                <div v-if="loading && !page" class="p-4 d-grid gap-2"><div v-for="i in 5" :key="i" class="skeleton" style="height: 44px"></div></div>
                <EmptyState v-else-if="loadError" icon="bi-wifi-off" title="Couldn't load this list" :text="loadError">
                    <button class="btn btn-soft" @click="load()">Try again</button>
                </EmptyState>
                <EmptyState v-else-if="page && !page.data.length" icon="bi-search" title="Nothing found" text="Try another search or filter." />

                <!-- Organisers -->
                <div v-else-if="tab === 'users' && page" class="table-responsive" :class="{ 'opacity-50': loading }">
                    <table class="table-b">
                        <thead><tr><th>Organiser</th><th>Joined</th><th>Events</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="u in (page.data as PlatformUser[])" :key="u.id">
                                <td>
                                    <div class="fw-semibold text-ink text-break">
                                        {{ u.name }}
                                        <span v-if="u.is_admin" class="pill pill-violet ms-1">Admin</span>
                                        <span v-else-if="u.pro_account_at" class="pill pill-violet ms-1"><i class="bi bi-gem"></i> Pro account</span>
                                    </div>
                                    <div class="small text-muted text-break">{{ u.email }}</div>
                                </td>
                                <td class="text-nowrap small">{{ formatDate(u.created_at) }}</td>
                                <td>{{ formatNumber(u.events_count) }}</td>
                                <td class="text-nowrap">
                                    <span v-if="u.suspended_at" class="pill pill-danger"><span class="dot"></span>Suspended</span>
                                    <span v-else-if="!u.email_verified_at" class="pill pill-upcoming"><span class="dot"></span>Unconfirmed</span>
                                    <span v-else class="pill pill-active"><span class="dot"></span>Active</span>
                                    <i v-if="u.two_factor_enabled" class="bi bi-shield-check text-brand ms-1" title="Two-step login on"></i>
                                </td>
                                <td class="text-end text-nowrap">
                                    <template v-if="!u.is_admin">
                                        <button class="btn btn-ghost btn-sm me-1" :disabled="acting === u.id" @click="toggleAccountPro(u)">
                                            <i class="bi" :class="u.pro_account_at ? 'bi-x-lg' : 'bi-gem'"></i> {{ u.pro_account_at ? 'Remove Pro' : 'Give Pro' }}
                                        </button>
                                        <template v-if="u.id !== auth.user?.id">
                                            <button v-if="u.suspended_at" class="btn btn-soft btn-sm" :disabled="acting === u.id" @click="unsuspend(u)">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                            <button v-else class="btn btn-danger-soft btn-sm" :disabled="acting === u.id" @click="suspend(u)">
                                                <i class="bi bi-slash-circle"></i> Suspend
                                            </button>
                                        </template>
                                    </template>
                                    <span v-else-if="u.id === auth.user?.id" class="small text-muted">You</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Events -->
                <div v-else-if="tab === 'events' && page" class="table-responsive" :class="{ 'opacity-50': loading }">
                    <table class="table-b">
                        <thead><tr><th>Event</th><th>Organiser</th><th>Status</th><th>Plan</th><th>Turnout</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="ev in (page.data as PlatformEvent[])" :key="ev.id">
                                <td>
                                    <div class="fw-semibold text-ink text-break">{{ ev.name }}</div>
                                    <div class="small text-muted">{{ ev.voting_mode === 'paid' ? 'Paid voting' : 'Secret ballot' }} · {{ formatDate(ev.created_at) }}</div>
                                </td>
                                <td>
                                    <div class="small text-ink text-break">{{ ev.user?.name ?? '—' }}</div>
                                    <div class="small text-muted text-break">
                                        {{ ev.user?.email }}
                                        <span v-if="ev.user?.suspended_at" class="text-danger fw-semibold">· suspended</span>
                                    </div>
                                </td>
                                <td><StatusBadge :status="ev.status" :draft="isDraft(ev)" /></td>
                                <td class="text-nowrap small">
                                    <span v-if="ev.plan === 'pro'" class="pill pill-violet"><i class="bi bi-gem"></i> Pro</span>
                                    <span v-else-if="ev.user?.pro_account_at || ev.user?.is_admin" class="pill pill-violet" title="Pro through the account">Pro (account)</span>
                                    <span v-else class="pill pill-completed">Free</span>
                                    <div v-if="ev.upgraded_via" class="text-muted mt-1">{{ ev.upgraded_via === 'payment' ? 'Paid' : 'By admin' }}</div>
                                </td>
                                <td class="text-nowrap small">
                                    {{ formatNumber(ev.voted_count) }} / {{ formatNumber(ev.voters_count) }}
                                    <span v-if="ev.voters_count" class="text-muted">({{ percent(ev.voted_count, ev.voters_count) }}%)</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button v-if="ev.plan === 'pro'" class="btn btn-ghost btn-sm" :disabled="acting === ev.id" @click="setPlan(ev, 'free')">Make Free</button>
                                    <button v-else-if="!ev.user?.pro_account_at && !ev.user?.is_admin" class="btn btn-soft btn-sm" :disabled="acting === ev.id" @click="setPlan(ev, 'pro')"><i class="bi bi-gem"></i> Make Pro</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Payments -->
                <div v-else-if="tab === 'payments' && page" class="table-responsive" :class="{ 'opacity-50': loading }">
                    <table class="table-b">
                        <thead><tr><th>Payment</th><th>Organiser</th><th>Event</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr v-for="pay in (page.data as PlatformPayment[])" :key="pay.id">
                                <td>
                                    <div class="mono small text-ink">{{ pay.reference }}</div>
                                    <div class="small text-muted">{{ formatDate(pay.paid_at ?? pay.created_at, true) }}<template v-if="pay.channel"> · {{ pay.channel }}</template></div>
                                </td>
                                <td>
                                    <div class="small text-ink text-break">{{ pay.user?.name ?? '—' }}</div>
                                    <div class="small text-muted text-break">{{ pay.user?.email }}</div>
                                </td>
                                <td class="small text-break">{{ pay.event_name ?? '—' }}<span v-if="!pay.event_id" class="text-muted"> (deleted)</span></td>
                                <td class="text-nowrap fw-semibold">{{ formatNaira(pay.amount_kobo / 100) }}</td>
                                <td class="text-nowrap">
                                    <span class="pill" :class="{ 'pill-active': pay.status === 'success', 'pill-upcoming': pay.status === 'pending', 'pill-danger': pay.status === 'failed' }">
                                        <span class="dot"></span>{{ paymentLabel[pay.status] }}
                                    </span>
                                    <div v-if="pay.failure_reason" class="small text-muted mt-1">{{ pay.failure_reason }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="page && page.last_page > 1" class="d-flex justify-content-between align-items-center px-3 py-2 border-top small">
                    <span class="text-muted">Page {{ page.current_page }} of {{ page.last_page }} · {{ formatNumber(page.total) }} total</span>
                    <div class="d-flex gap-1">
                        <button class="btn btn-ghost btn-sm" :disabled="loading || page.current_page <= 1" @click="go(page.current_page - 1)"><i class="bi bi-chevron-left"></i> Prev</button>
                        <button class="btn btn-ghost btn-sm" :disabled="loading || page.current_page >= page.last_page" @click="go(page.current_page + 1)">Next <i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { platformApi } from '~/api/endpoints/admin';
import { useAuthStore } from '~/stores/auth';
import type { Paginated, PlanSettings, PlatformEvent, PlatformOverview, PlatformPayment, PlatformUser } from '~/types';

definePageMeta({ layout: 'dashboard', middleware: ['auth', 'admin'] });
useHead({ title: 'Platform admin · Ballotify' });

const auth = useAuthStore();
const toast = useToast();
const { confirm } = useConfirm();
const apiBase = String(useRuntimeConfig().public.apiBase || '').replace(/\/+$/, '');
const webhookUrl = `${apiBase}/paystack/webhook`;
const flutterwaveWebhookUrl = `${apiBase}/flutterwave/webhook`;
const providerPills = computed(() => {
    const on = pricingStatus.value?.payment_providers ?? [];
    return [
        { key: 'paystack', label: 'Paystack', on: on.includes('paystack'), backup: false },
        { key: 'flutterwave', label: 'Flutterwave', on: on.includes('flutterwave'), backup: on.includes('paystack') },
    ];
});

type TabKey = 'users' | 'events' | 'payments' | 'payouts' | 'pricing' | 'health';
const tabs: { key: TabKey; label: string; icon: string }[] = [
    { key: 'users', label: 'Organisers', icon: 'bi-people' },
    { key: 'events', label: 'Events', icon: 'bi-calendar2-week' },
    { key: 'payments', label: 'Payments', icon: 'bi-receipt' },
    { key: 'payouts', label: 'Payouts', icon: 'bi-bank' },
    { key: 'pricing', label: 'Pricing', icon: 'bi-tags' },
    { key: 'health', label: 'Health', icon: 'bi-heart-pulse' },
];
const paymentLabel = { success: 'Paid', pending: 'Pending', failed: 'Failed' } as const;

const ov = ref<PlatformOverview | null>(null);
const tab = ref<TabKey>('users');
const search = ref('');
const filter = ref('all');
type Row = PlatformUser | PlatformEvent | PlatformPayment;
const page = ref<Paginated<Row> | null>(null);
const loading = ref(false);
const loadError = ref('');
const acting = ref<number | null>(null);

const searchPlaceholder = computed(() => ({
    users: 'Search name or email', events: 'Search event or organiser', payments: 'Search reference, event or organiser', pricing: '', payouts: '', health: '',
})[tab.value]);

const filters = computed(() => {
    switch (tab.value) {
        case 'users': return [{ value: 'all', label: 'All' }, { value: 'pro', label: 'Pro accounts' }, { value: 'suspended', label: 'Suspended' }, { value: 'unverified', label: 'Unconfirmed' }, { value: 'admins', label: 'Admins' }];
        case 'events': return [{ value: 'all', label: 'All' }, { value: 'active', label: 'Open' }, { value: 'upcoming', label: 'Scheduled' }, { value: 'completed', label: 'Closed' }];
        default: return [{ value: 'all', label: 'All' }, { value: 'success', label: 'Paid' }, { value: 'pending', label: 'Pending' }, { value: 'failed', label: 'Failed' }];
    }
});

let requestId = 0;
async function load(pageNo = 1) {
    if (tab.value === 'pricing' || tab.value === 'payouts' || tab.value === 'health') return;
    const mine = ++requestId;
    loading.value = true;
    loadError.value = '';
    try {
        const term = search.value.trim() || undefined;
        const f = filter.value === 'all' ? undefined : filter.value;
        let data: Paginated<Row>;
        if (tab.value === 'users') data = (await platformApi.users({ search: term, filter: f, page: pageNo })).data;
        else if (tab.value === 'events') data = (await platformApi.events({ search: term, status: f, page: pageNo })).data;
        else data = (await platformApi.payments({ search: term, status: f, page: pageNo })).data;
        if (mine === requestId) page.value = data;
    } catch (e) {
        if (mine === requestId) loadError.value = apiErrorMessage(e);
    } finally {
        if (mine === requestId) loading.value = false;
    }
}
const go = (n: number) => load(n);

async function loadOverview() {
    try { ov.value = (await platformApi.overview()).data; } catch { /* the stats are optional */ }
}

watch(tab, (t) => {
    filter.value = 'all';
    search.value = '';
    page.value = null;
    if (t === 'pricing') loadPricing();
    else load();
});
watch(filter, () => load());
let debounce: ReturnType<typeof setTimeout> | undefined;
watch(search, () => { clearTimeout(debounce); debounce = setTimeout(() => load(), 300); });
onBeforeUnmount(() => clearTimeout(debounce));

onMounted(() => { loadOverview(); load(); });

function replaceRow<T extends { id: number }>(updated: T) {
    if (!page.value) return;
    page.value = { ...page.value, data: page.value.data.map(r => (r.id === updated.id ? { ...r, ...updated } : r)) };
}

// ----- Organisers -----
/** What suspending does to the organiser's events, worded for 0, 1 or many. */
function suspendImpact(count: number): string {
    if (count === 0) return 'They have no events yet.';
    if (count === 1) return 'Their event will go offline: its voting link and results page will stop working.';
    return `All ${count} of their events will go offline: voting links and results pages will stop working.`;
}

async function suspend(u: PlatformUser) {
    const ok = await confirm({
        title: `Suspend ${u.name}?`,
        message: `They’ll be signed out everywhere and won’t be able to sign in. ${suspendImpact(u.events_count)} You can restore the account at any time.`,
        confirmText: 'Suspend account',
        danger: true,
    });
    if (!ok) return;
    await act(u.id, async () => {
        replaceRow((await platformApi.suspend(u.id)).data);
        toast.success(`${u.name} suspended`);
        loadOverview();
    });
}

async function unsuspend(u: PlatformUser) {
    await act(u.id, async () => {
        replaceRow((await platformApi.unsuspend(u.id)).data);
        toast.success(`${u.name} restored`);
        loadOverview();
    });
}

async function toggleAccountPro(u: PlatformUser) {
    const enable = !u.pro_account_at;
    const ok = await confirm(enable
        ? { title: `Give ${u.name} a Pro account?`, message: 'Every event on this account, current and future, becomes Pro without payment: more voters, email and SMS invitations, no branding.', confirmText: 'Give Pro' }
        : { title: `Remove Pro from ${u.name}?`, message: 'Their events go back to the free limits, except events that were paid for or upgraded individually. Existing voters are kept, but they can’t add more beyond the free limit.', confirmText: 'Remove Pro', danger: true });
    if (!ok) return;
    await act(u.id, async () => {
        replaceRow((await platformApi.setAccountPro(u.id, enable)).data);
        toast.success(enable ? `${u.name} now has a Pro account` : `Pro removed from ${u.name}`);
    });
}

// ----- Events -----
async function setPlan(ev: PlatformEvent, plan: 'free' | 'pro') {
    if (plan === 'free') {
        const ok = await confirm({
            title: `Set “${ev.name}” back to Free?`,
            message: ev.upgraded_via === 'payment'
                ? 'This organiser PAID for Pro on this event. Only do this if you have refunded them.'
                : 'Free limits will apply again. Existing voters are kept.',
            confirmText: 'Make Free',
            danger: true,
        });
        if (!ok) return;
    }
    await act(ev.id, async () => {
        const { data } = await platformApi.setEventPlan(ev.id, plan);
        replaceRow({ ...ev, plan: data.plan, upgraded_via: data.upgraded_via });
        toast.success(plan === 'pro' ? `“${ev.name}” is now Pro` : `“${ev.name}” is now Free`);
        loadOverview();
    });
}

async function act(id: number, fn: () => Promise<void>) {
    acting.value = id;
    try { await fn(); } catch (e) { toast.error(apiErrorMessage(e)); } finally { acting.value = null; }
}

// ----- Pricing -----
const pricing = reactive({ pro_price_naira: 0, pro_max_voters: 0, free_max_voters: 0, free_max_active_events: 1, paid_vote_fee_percent: 10 });
const pricingStatus = ref<PlanSettings | null>(null);
const { loadPlans: refreshPlans } = useBilling();
const savingPricing = ref(false);

async function loadPricing() {
    try {
        const { data } = await platformApi.plans();
        pricingStatus.value = data;
        Object.assign(pricing, {
            pro_price_naira: data.pro_price_naira,
            pro_max_voters: data.pro_max_voters,
            free_max_voters: data.free_max_voters,
            free_max_active_events: data.free_max_active_events,
            paid_vote_fee_percent: data.paid_vote_fee_percent,
        });
    } catch (e) {
        toast.error(apiErrorMessage(e));
    }
}

async function savePricing() {
    savingPricing.value = true;
    try {
        pricingStatus.value = (await platformApi.updatePlans({ ...pricing })).data;
        refreshPlans(true);
        toast.success('Prices and limits saved');
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        savingPricing.value = false;
    }
}
</script>
