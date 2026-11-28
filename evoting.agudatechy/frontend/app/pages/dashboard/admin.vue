<template>
    <div>
        <PageHeader title="Platform admin" subtitle="Approve new accounts and keep an eye on the platform. Ballot contents, voter lists and codes are never shown here." />

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <StatCard label="Waiting for approval" :value="ov ? formatNumber(ov.awaiting_approval) : '–'" icon="bi-hourglass-split" tone="amber"
                    :hint="ov && ov.awaiting_approval ? 'Review them below' : 'Nobody waiting'" />
            </div>
            <div class="col-6 col-xl-3">
                <StatCard label="Organisers" :value="ov ? formatNumber(ov.users) : '–'" icon="bi-person-badge"
                    :hint="ov ? `+${formatNumber(ov.users_last_30_days)} in 30 days` : ''" />
            </div>
            <div class="col-6 col-xl-3">
                <StatCard label="Events" :value="ov ? formatNumber(ov.events) : '–'" icon="bi-calendar2-check" tone="violet"
                    :hint="ov ? `${formatNumber(ov.events_live)} open now` : ''" />
            </div>
            <div class="col-6 col-xl-3">
                <StatCard label="Ballots cast" :value="ov ? formatNumber(ov.ballots_cast) : '–'" icon="bi-check2-square" tone="green"
                    :hint="ov ? `${formatNumber(ov.suspended_users)} suspended account${ov.suspended_users === 1 ? '' : 's'}` : ''" />
            </div>
        </div>

        <div class="segmented mb-3" role="tablist">
            <button v-for="t in tabs" :key="t.key" type="button" role="tab" :aria-selected="tab === t.key" :class="{ active: tab === t.key }" @click="tab = t.key">
                <i class="bi" :class="t.icon"></i> {{ t.label }}
                <span v-if="t.key === 'users' && ov?.awaiting_approval" class="count-dot">{{ ov.awaiting_approval }}</span>
            </button>
        </div>

        <AdminHealth v-if="tab === 'health'" />
        <AdminAccountLog v-else-if="tab === 'log'" />

        <div v-else class="card-b overflow-hidden">
            <div class="list-tools">
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-search"></i>
                    <input v-model="search" type="search" class="form-control" :placeholder="tab === 'users' ? 'Search name or email' : 'Search event or organiser'" @keydown.enter="load(1)" />
                </div>
                <AppSelect v-if="tab === 'users'" v-model="userFilter" :options="USER_FILTERS" :block="false" class="filter-select" aria-label="Show accounts" @change="load(1)" />
                <AppSelect v-else v-model="eventStatus" :options="EVENT_FILTERS" :block="false" class="filter-select" aria-label="Show events" @change="load(1)" />
            </div>

            <div v-if="loading && !page" class="p-4 d-grid gap-2"><div v-for="i in 5" :key="i" class="skeleton" style="height: 38px"></div></div>
            <EmptyState v-else-if="page && !page.data.length" icon="bi-search"
                :title="tab === 'users' && userFilter === 'awaiting' ? 'Nobody is waiting for approval' : 'Nothing to show'" />

            <!-- Accounts -->
            <div v-else-if="tab === 'users' && page" class="table-responsive" :class="{ 'opacity-50': loading }">
                <table class="table-b">
                    <thead><tr><th>Organiser</th><th>Joined</th><th>Events</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="u in (page.data as PlatformUser[])" :key="u.id">
                            <td>
                                <div class="fw-semibold text-ink text-break">{{ u.name }} <span v-if="u.is_admin" class="pill pill-violet ms-1">Admin</span></div>
                                <div class="small text-muted text-break">{{ u.email }}</div>
                            </td>
                            <td class="small text-nowrap">{{ formatDate(u.created_at ?? null) }}</td>
                            <td>{{ formatNumber(u.events_count) }}</td>
                            <td class="text-nowrap">
                                <span v-if="u.suspended_at" class="pill pill-danger"><span class="dot"></span>Suspended</span>
                                <span v-else-if="u.awaiting_approval" class="pill pill-upcoming"><span class="dot"></span>Waiting for approval</span>
                                <span v-else class="pill pill-active"><span class="dot"></span>Approved</span>
                                <div v-if="!u.email_verified_at" class="small text-muted mt-1">Email not confirmed</div>
                            </td>
                            <td class="text-end text-nowrap">
                                <template v-if="!u.is_admin">
                                    <button v-if="u.awaiting_approval" class="btn btn-primary btn-sm me-1" :disabled="acting === u.id" @click="approve(u, true)">
                                        <i class="bi bi-check2"></i> Approve
                                    </button>
                                    <button v-else class="btn btn-ghost btn-sm me-1" :disabled="acting === u.id" @click="approve(u, false)">Withdraw approval</button>
                                    <button v-if="u.suspended_at" class="btn btn-soft btn-sm" :disabled="acting === u.id" @click="unsuspend(u)">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                    </button>
                                    <button v-else class="btn btn-ghost btn-sm text-danger" :disabled="acting === u.id" @click="suspend(u)">Suspend</button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Events -->
            <div v-else-if="tab === 'events' && page" class="table-responsive" :class="{ 'opacity-50': loading }">
                <table class="table-b">
                    <thead><tr><th>Event</th><th>Organiser</th><th>Status</th><th>Turnout</th></tr></thead>
                    <tbody>
                        <tr v-for="ev in (page.data as PlatformEvent[])" :key="ev.id">
                            <td>
                                <div class="fw-semibold text-ink text-break">{{ ev.name }}</div>
                                <div class="small text-muted">{{ formatWindow(ev.starts_at, ev.ends_at) }}</div>
                            </td>
                            <td>
                                <div class="text-ink text-break">{{ ev.user?.name ?? '—' }}</div>
                                <div class="small text-muted text-break">
                                    {{ ev.user?.email }}
                                    <span v-if="ev.user?.suspended_at" class="text-danger fw-semibold">· suspended</span>
                                </div>
                            </td>
                            <td><StatusBadge :status="ev.status" :draft="isDraft(ev)" /></td>
                            <td class="text-nowrap small">
                                {{ formatNumber(ev.voted_count) }} / {{ formatNumber(ev.voters_count) }}
                                <span v-if="ev.voters_count" class="text-muted">({{ percent(ev.voted_count, ev.voters_count) }}%)</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="page && page.last_page > 1" class="d-flex justify-content-between align-items-center px-3 py-2 border-top small">
                <span class="text-muted">Page {{ page.current_page }} of {{ page.last_page }} · {{ formatNumber(page.total) }} in all</span>
                <div class="d-flex gap-1">
                    <button class="btn btn-ghost btn-sm" :disabled="page.current_page <= 1 || loading" @click="load(page.current_page - 1)">Previous</button>
                    <button class="btn btn-ghost btn-sm" :disabled="page.current_page >= page.last_page || loading" @click="load(page.current_page + 1)">Next</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { platformApi } from '~/api/endpoints/admin';
import type { Paginated, PlatformEvent, PlatformOverview, PlatformUser } from '~/types';

definePageMeta({ layout: 'dashboard', middleware: ['auth', 'admin'] });
useHead({ title: `Admin · ${String(useRuntimeConfig().public.companyName || 'E-voting by Agudatechy')}` });

const toast = useToast();
const { confirm } = useConfirm();

type Tab = 'users' | 'events' | 'log' | 'health';
const tabs: { key: Tab; label: string; icon: string }[] = [
    { key: 'users', label: 'Accounts', icon: 'bi-people' },
    { key: 'events', label: 'Events', icon: 'bi-calendar2-week' },
    { key: 'log', label: 'Log', icon: 'bi-journal-text' },
    { key: 'health', label: 'Health', icon: 'bi-heart-pulse' },
];
const USER_FILTERS = [
    { value: 'awaiting', label: 'Waiting for approval' },
    { value: 'approved', label: 'Approved' },
    { value: 'suspended', label: 'Suspended' },
    { value: 'unverified', label: 'Email not confirmed' },
    { value: 'all', label: 'All accounts' },
];
const EVENT_FILTERS = [
    { value: '', label: 'All events' },
    { value: 'active', label: 'Open now' },
    { value: 'upcoming', label: 'Not started' },
    { value: 'completed', label: 'Closed' },
];

const tab = ref<Tab>('users');
const ov = ref<PlatformOverview | null>(null);
const page = ref<Paginated<PlatformUser | PlatformEvent> | null>(null);
const loading = ref(false);
const acting = ref<number | null>(null);
const search = ref('');
// Open on the accounts that need something done; everything else is one click away.
const userFilter = ref('awaiting');
const eventStatus = ref('');

async function loadOverview() {
    try { ov.value = (await platformApi.overview()).data; } catch { /* the cards just show a dash */ }
}

async function load(p = 1) {
    if (tab.value === 'health' || tab.value === 'log') return;
    loading.value = true;
    try {
        page.value = tab.value === 'users'
            ? (await platformApi.users({ search: search.value || undefined, filter: userFilter.value, page: p })).data
            : (await platformApi.events({ search: search.value || undefined, status: eventStatus.value || undefined, page: p })).data;
    } catch (e) {
        toast.error(apiErrorMessage(e));
    } finally {
        loading.value = false;
    }
}

watch(tab, () => { page.value = null; search.value = ''; load(1); });
onMounted(() => { loadOverview(); load(1); });

function replaceRow(u: PlatformUser) {
    if (!page.value) return;
    page.value = { ...page.value, data: (page.value.data as PlatformUser[]).map(x => (x.id === u.id ? { ...x, ...u } : x)) };
}

async function act(id: number, fn: () => Promise<void>) {
    acting.value = id;
    try { await fn(); } catch (e) { toast.error(apiErrorMessage(e)); } finally { acting.value = null; }
}

async function approve(u: PlatformUser, approved: boolean) {
    if (!approved) {
        const ok = await confirm({
            title: `Withdraw approval for ${u.name}?`,
            message: 'They keep their existing events but can’t create new ones until you approve them again.',
            confirmText: 'Withdraw approval',
            danger: true,
        });
        if (!ok) return;
    }
    await act(u.id, async () => {
        replaceRow((await platformApi.setApproval(u.id, approved)).data);
        toast.success(approved ? `${u.name} is approved and can create events` : `Approval withdrawn for ${u.name}`);
        loadOverview();
    });
}

async function suspend(u: PlatformUser) {
    const ok = await confirm({
        title: `Suspend ${u.name}?`,
        message: 'They are signed out everywhere and can’t sign in until you restore the account. Their events stay as they are.',
        confirmText: 'Suspend',
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
</script>

<style scoped>
.list-tools { display: flex; gap: .6rem; flex-wrap: wrap; padding: .85rem 1rem; border-bottom: 1px solid var(--line); }
.filter-select { min-width: 200px; }
@media (max-width: 575.98px) { .filter-select { min-width: 0; flex: 1 1 100%; } }
.count-dot { display: inline-grid; place-items: center; min-width: 20px; height: 20px; padding: 0 .35rem; border-radius: 999px; background: var(--warning); color: #fff; font-size: .72rem; font-weight: 800; margin-left: .3rem; }
</style>
